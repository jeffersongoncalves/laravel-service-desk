<?php

use Illuminate\Support\Facades\Notification;
use JeffersonGoncalves\ServiceDesk\Events\TicketAssigned;
use JeffersonGoncalves\ServiceDesk\Listeners\SendTicketAssignedNotification;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketAssignedNotification;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->listener = new SendTicketAssignedNotification;
    $this->ticket = Ticket::factory()->create();
    $this->operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);
});

it('notifies the assigned operator', function () {
    Notification::fake();

    $this->listener->handle(new TicketAssigned($this->ticket, $this->operator));

    Notification::assertSentTo($this->operator, TicketAssignedNotification::class);
});

it('does not send anything when ticket_assigned notifications are disabled', function () {
    Notification::fake();
    config()->set('service-desk.notifications.notify_on.ticket_assigned', false);

    $this->listener->handle(new TicketAssigned($this->ticket, $this->operator));

    Notification::assertNothingSent();
});
