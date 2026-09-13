<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Events\InboundEmailProcessed;
use JeffersonGoncalves\ServiceDesk\Events\InboundEmailReceived;
use JeffersonGoncalves\ServiceDesk\Listeners\ProcessInboundEmail;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;
use JeffersonGoncalves\ServiceDesk\Models\InboundEmail;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->listener = app(ProcessInboundEmail::class);
});

function makeInboundEmail(array $overrides = []): InboundEmail
{
    return InboundEmail::create(array_merge([
        'message_id' => '<'.uniqid('inbound-', true).'@example.com>',
        'from_address' => 'sender@example.com',
        'to_addresses' => ['support@example.com'],
        'subject' => 'Need help',
        'text_body' => 'Something is broken.',
        'status' => 'pending',
    ], $overrides));
}

it('ignores the email when no matching user is found', function () {
    Event::fake([InboundEmailProcessed::class]);

    $email = makeInboundEmail(['from_address' => 'unknown@example.com']);

    $this->listener->handle(new InboundEmailReceived($email));

    expect($email->fresh()->status)->toBe('ignored');
    Event::assertNotDispatched(InboundEmailProcessed::class);
});

it('creates a new ticket when no existing thread matches', function () {
    Event::fake([InboundEmailProcessed::class]);

    $department = Department::factory()->create(['is_active' => true]);
    $user = User::create(['name' => 'Customer', 'email' => 'sender@example.com']);

    $email = makeInboundEmail(['from_address' => 'sender@example.com']);

    $this->listener->handle(new InboundEmailReceived($email));

    $email->refresh();

    expect($email->status)->toBe('processed')
        ->and($email->ticket_id)->not->toBeNull();

    $ticket = Ticket::find($email->ticket_id);

    expect($ticket->title)->toBe('Need help')
        ->and($ticket->user_id)->toBe($user->id)
        ->and($ticket->department_id)->toBe($department->id);

    Event::assertDispatched(InboundEmailProcessed::class);
});

it('adds a reply to the existing ticket when the thread is matched by in_reply_to', function () {
    Event::fake([InboundEmailProcessed::class]);

    Department::factory()->create();
    $user = User::create(['name' => 'Customer', 'email' => 'sender@example.com']);
    $ticket = Ticket::factory()->create([
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
        'email_message_id' => '<original@example.com>',
    ]);

    $email = makeInboundEmail([
        'from_address' => 'sender@example.com',
        'in_reply_to' => '<original@example.com>',
    ]);

    $this->listener->handle(new InboundEmailReceived($email));

    $email->refresh();

    expect($email->status)->toBe('processed')
        ->and($email->ticket_id)->toBe($ticket->id)
        ->and($email->comment_id)->not->toBeNull();

    expect($ticket->comments)->toHaveCount(1);
});

it('resolves the department from the matching email channel', function () {
    Event::fake([InboundEmailProcessed::class]);

    $channelDepartment = Department::factory()->create();
    User::create(['name' => 'Customer', 'email' => 'sender@example.com']);

    $channel = EmailChannel::create([
        'department_id' => $channelDepartment->id,
        'name' => 'Support Channel',
        'driver' => 'mailgun',
        'email_address' => 'support@example.com',
        'settings' => [],
        'is_active' => true,
    ]);

    $email = makeInboundEmail([
        'from_address' => 'sender@example.com',
        'email_channel_id' => $channel->id,
    ]);

    $this->listener->handle(new InboundEmailReceived($email));

    $ticket = Ticket::find($email->fresh()->ticket_id);

    expect($ticket->department_id)->toBe($channelDepartment->id);
});
