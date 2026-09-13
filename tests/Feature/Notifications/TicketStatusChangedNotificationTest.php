<?php

use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketStatusChangedNotification;

beforeEach(function () {
    $this->ticket = Ticket::factory()->create(['title' => 'Cannot print']);
});

it('sends via the configured notification channels', function () {
    config()->set('service-desk.notifications.channels', ['mail']);

    $notification = new TicketStatusChangedNotification($this->ticket, TicketStatus::Open, TicketStatus::InProgress);

    expect($notification->via((object) []))->toBe(['mail']);
});

it('builds a mail message referencing the ticket', function () {
    $notification = new TicketStatusChangedNotification($this->ticket, TicketStatus::Open, TicketStatus::InProgress);

    $mail = $notification->toMail((object) []);

    expect($mail->subject)->toContain($this->ticket->reference_number);
});

it('builds an array payload with the status transition', function () {
    $notification = new TicketStatusChangedNotification($this->ticket, TicketStatus::Open, TicketStatus::InProgress);

    $array = $notification->toArray((object) []);

    expect($array)->toMatchArray([
        'ticket_id' => $this->ticket->id,
        'ticket_uuid' => $this->ticket->uuid,
        'reference_number' => $this->ticket->reference_number,
        'title' => 'Cannot print',
        'old_status' => 'open',
        'new_status' => 'in_progress',
        'type' => 'ticket_status_changed',
    ]);
});
