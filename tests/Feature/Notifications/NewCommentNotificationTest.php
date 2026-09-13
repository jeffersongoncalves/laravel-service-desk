<?php

use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Notifications\NewCommentNotification;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->ticket = Ticket::factory()->create(['title' => 'Cannot print']);
    $this->author = User::create(['name' => 'Author', 'email' => 'author@example.com']);
    $this->comment = TicketComment::factory()->for($this->ticket)->create([
        'author_type' => $this->author->getMorphClass(),
        'author_id' => $this->author->id,
        'body' => 'Working on it',
    ]);
});

it('sends via the configured notification channels', function () {
    config()->set('service-desk.notifications.channels', ['mail']);

    $notification = new NewCommentNotification($this->ticket, $this->comment);

    expect($notification->via((object) []))->toBe(['mail']);
});

it('builds a mail message referencing the ticket', function () {
    $notification = new NewCommentNotification($this->ticket, $this->comment);

    $mail = $notification->toMail((object) []);

    expect($mail->subject)->toContain($this->ticket->reference_number);
});

it('builds an array payload with the comment data', function () {
    $notification = new NewCommentNotification($this->ticket, $this->comment);

    $array = $notification->toArray((object) []);

    expect($array)->toMatchArray([
        'ticket_id' => $this->ticket->id,
        'ticket_uuid' => $this->ticket->uuid,
        'reference_number' => $this->ticket->reference_number,
        'title' => 'Cannot print',
        'comment_id' => $this->comment->id,
        'author_name' => 'Author',
        'type' => 'new_comment',
    ]);
});
