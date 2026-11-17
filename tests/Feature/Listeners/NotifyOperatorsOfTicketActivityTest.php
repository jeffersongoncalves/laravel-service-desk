<?php

use Illuminate\Support\Facades\Notification;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Listeners\NotifyOperatorsOfTicketActivity;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Notifications\OperatorTicketActivityNotification;
use JeffersonGoncalves\ServiceDesk\Services\DepartmentService;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    Notification::fake();

    $this->listener = new NotifyOperatorsOfTicketActivity;
    $this->department = Department::factory()->create();
    $this->requester = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);
    $this->alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $this->bob = User::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    app(DepartmentService::class)->addOperator($this->department, $this->alice);
    app(DepartmentService::class)->addOperator($this->department, $this->bob);

    $this->ticket = Ticket::factory()->create([
        'department_id' => $this->department->id,
        'user_type' => $this->requester->getMorphClass(),
        'user_id' => $this->requester->id,
    ]);
});

function requesterReply(Ticket $ticket, User $author, bool $internal = false): CommentAdded
{
    $comment = TicketComment::factory()->for($ticket)->create([
        'author_type' => $author->getMorphClass(),
        'author_id' => $author->id,
        'is_internal' => $internal,
    ]);

    return new CommentAdded($ticket, $comment);
}

it('emails every department operator about an unassigned new ticket', function () {
    $this->listener->handle(new TicketCreated($this->ticket));

    Notification::assertSentTo([$this->alice, $this->bob], OperatorTicketActivityNotification::class);
    Notification::assertNotSentTo($this->requester, OperatorTicketActivityNotification::class);
});

it('skips operators who turned email off for the department', function () {
    $this->alice->serviceDeskDepartments()->updateExistingPivot($this->department->id, ['email_notifications' => false]);

    $this->listener->handle(new TicketCreated($this->ticket));

    Notification::assertNotSentTo($this->alice, OperatorTicketActivityNotification::class);
    Notification::assertSentTo($this->bob, OperatorTicketActivityNotification::class);
});

it('emails only the assignee once the ticket is assigned', function () {
    $this->ticket->update(['assigned_to_type' => $this->alice->getMorphClass(), 'assigned_to_id' => $this->alice->id]);

    $this->listener->handle(requesterReply($this->ticket->fresh(), $this->requester));

    Notification::assertSentTo($this->alice, OperatorTicketActivityNotification::class);
    Notification::assertNotSentTo($this->bob, OperatorTicketActivityNotification::class);
});

it('respects the assignee opting out', function () {
    $this->ticket->update(['assigned_to_type' => $this->alice->getMorphClass(), 'assigned_to_id' => $this->alice->id]);
    $this->alice->serviceDeskDepartments()->updateExistingPivot($this->department->id, ['email_notifications' => false]);

    $this->listener->handle(new TicketCreated($this->ticket->fresh()));

    Notification::assertNothingSent();
});

it('ignores operator replies and internal notes', function () {
    $this->listener->handle(requesterReply($this->ticket, $this->alice));
    $this->listener->handle(requesterReply($this->ticket, $this->requester, internal: true));

    Notification::assertNothingSent();
});

it('leaves watchers to the comment notification', function () {
    app(TicketService::class)->addWatcher($this->ticket, $this->alice);

    $this->listener->handle(requesterReply($this->ticket->fresh(), $this->requester));

    Notification::assertNotSentTo($this->alice, OperatorTicketActivityNotification::class);
    Notification::assertSentTo($this->bob, OperatorTicketActivityNotification::class);
});

it('can be turned off in config', function () {
    config()->set('service-desk.notifications.notify_on.operator_new_ticket', false);
    config()->set('service-desk.notifications.notify_on.operator_requester_reply', false);

    $this->listener->handle(new TicketCreated($this->ticket));
    $this->listener->handle(requesterReply($this->ticket, $this->requester));

    Notification::assertNothingSent();
});

it('builds a mail with the ticket link', function () {
    Ticket::resolveNotificationUrlUsing(fn (Ticket $ticket, object $notifiable, bool $forOperator): string => ($forOperator ? 'https://agent/' : 'https://user/').$ticket->uuid);

    $mail = (new OperatorTicketActivityNotification($this->ticket))->toMail($this->alice);

    expect($mail->subject)->toContain($this->ticket->reference_number)
        ->and($mail->actionUrl)->toBe('https://agent/'.$this->ticket->uuid)
        ->and((new OperatorTicketActivityNotification($this->ticket))->toMail($this->requester)->actionUrl)
        ->toBe('https://user/'.$this->ticket->uuid);
});
