<?php

use Illuminate\Support\Facades\Notification;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Listeners\SendTicketCreatedNotification;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketCreatedNotification;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->listener = new SendTicketCreatedNotification;
    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.com']);
    $this->ticket = Ticket::factory()->create([
        'user_type' => $this->owner->getMorphClass(),
        'user_id' => $this->owner->id,
    ]);
});

it('notifies the ticket owner', function () {
    Notification::fake();

    $this->listener->handle(new TicketCreated($this->ticket));

    Notification::assertSentTo($this->owner, TicketCreatedNotification::class);
});

it('does not send anything when ticket_created notifications are disabled', function () {
    Notification::fake();
    config()->set('service-desk.notifications.notify_on.ticket_created', false);

    $this->listener->handle(new TicketCreated($this->ticket));

    Notification::assertNothingSent();
});
