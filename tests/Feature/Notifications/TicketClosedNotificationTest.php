<?php

use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketClosedNotification;

beforeEach(function () {
    $this->ticket = Ticket::factory()->create(['title' => 'Cannot print', 'closed_at' => now()]);
});

it('sends via the configured notification channels', function () {
    config()->set('service-desk.notifications.channels', ['mail']);

    $notification = new TicketClosedNotification($this->ticket);

    expect($notification->via((object) []))->toBe(['mail']);
});

it('builds a mail message referencing the ticket', function () {
    $notification = new TicketClosedNotification($this->ticket);

    $mail = $notification->toMail((object) []);

    expect($mail->subject)->toContain($this->ticket->reference_number);
});

it('builds an array payload with the ticket data', function () {
    $notification = new TicketClosedNotification($this->ticket);

    $array = $notification->toArray((object) []);

    expect($array)->toMatchArray([
        'ticket_id' => $this->ticket->id,
        'ticket_uuid' => $this->ticket->uuid,
        'reference_number' => $this->ticket->reference_number,
        'title' => 'Cannot print',
        'closed_at' => $this->ticket->closed_at->toISOString(),
        'type' => 'ticket_closed',
    ]);
});
