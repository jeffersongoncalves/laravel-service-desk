<?php

use Illuminate\Support\Facades\Notification;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Listeners\SendTicketStatusChangedNotification;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketStatusChangedNotification;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->listener = new SendTicketStatusChangedNotification;
    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.com']);
    $this->ticket = Ticket::factory()->create([
        'user_type' => $this->owner->getMorphClass(),
        'user_id' => $this->owner->id,
    ]);
});

it('notifies the ticket owner about the status change', function () {
    Notification::fake();

    $this->listener->handle(new TicketStatusChanged($this->ticket, TicketStatus::Open, TicketStatus::InProgress));

    Notification::assertSentTo($this->owner, TicketStatusChangedNotification::class);
});

it('does not send anything when ticket_status_changed notifications are disabled', function () {
    Notification::fake();
    config()->set('service-desk.notifications.notify_on.ticket_status_changed', false);

    $this->listener->handle(new TicketStatusChanged($this->ticket, TicketStatus::Open, TicketStatus::InProgress));

    Notification::assertNothingSent();
});
