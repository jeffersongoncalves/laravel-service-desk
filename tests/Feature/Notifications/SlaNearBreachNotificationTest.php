<?php

use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Notifications\SlaNearBreachNotification;

beforeEach(function () {
    $this->ticket = Ticket::factory()->create(['title' => 'Cannot print']);
});

it('sends via the configured notification channels', function () {
    config()->set('service-desk.notifications.channels', ['mail']);

    $notification = new SlaNearBreachNotification($this->ticket, 'resolution', 15);

    expect($notification->via((object) []))->toBe(['mail']);
});

it('builds a mail message referencing the ticket', function () {
    $notification = new SlaNearBreachNotification($this->ticket, 'resolution', 15);

    $mail = $notification->toMail((object) []);

    expect($mail->subject)->toContain($this->ticket->reference_number);
});

it('builds an array payload with the breach type and minutes remaining', function () {
    $notification = new SlaNearBreachNotification($this->ticket, 'resolution', 15);

    $array = $notification->toArray((object) []);

    expect($array)->toMatchArray([
        'ticket_id' => $this->ticket->id,
        'ticket_uuid' => $this->ticket->uuid,
        'reference_number' => $this->ticket->reference_number,
        'title' => 'Cannot print',
        'breach_type' => 'resolution',
        'minutes_remaining' => 15,
        'type' => 'sla_near_breach',
    ]);
});
