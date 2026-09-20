<?php

use Illuminate\Notifications\Messages\MailMessage;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Notifications\NewCommentNotification;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketAssignedNotification;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketClosedNotification;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketCreatedNotification;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketStatusChangedNotification;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\IdentificationHeader;

function renderSymfonyHeaders(MailMessage $mail): Headers
{
    $email = new Email;

    foreach ($mail->callbacks as $callback) {
        $callback($email);
    }

    return $email->getHeaders();
}

it('sets a valid Message-ID identification header on ticket created', function () {
    $ticket = Ticket::factory()->create();

    $headers = renderSymfonyHeaders((new TicketCreatedNotification($ticket))->toMail((object) []));

    expect($headers->get('Message-ID'))->toBeInstanceOf(IdentificationHeader::class);
});

it('sets a valid Message-ID identification header on ticket assigned', function () {
    $ticket = Ticket::factory()->create();

    $headers = renderSymfonyHeaders((new TicketAssignedNotification($ticket))->toMail((object) []));

    expect($headers->get('Message-ID'))->toBeInstanceOf(IdentificationHeader::class);
});

it('sets a valid Message-ID identification header on ticket closed', function () {
    $ticket = Ticket::factory()->create();

    $headers = renderSymfonyHeaders((new TicketClosedNotification($ticket))->toMail((object) []));

    expect($headers->get('Message-ID'))->toBeInstanceOf(IdentificationHeader::class);
});

it('sets a valid Message-ID identification header on ticket status changed', function () {
    $ticket = Ticket::factory()->create();

    $headers = renderSymfonyHeaders(
        (new TicketStatusChangedNotification($ticket, TicketStatus::Open, TicketStatus::InProgress))->toMail((object) [])
    );

    expect($headers->get('Message-ID'))->toBeInstanceOf(IdentificationHeader::class);
});

it('sets a valid Message-ID identification header on new comment', function () {
    $ticket = Ticket::factory()->create();
    $comment = TicketComment::factory()->for($ticket)->create();

    $headers = renderSymfonyHeaders((new NewCommentNotification($ticket, $comment))->toMail((object) []));

    expect($headers->get('Message-ID'))->toBeInstanceOf(IdentificationHeader::class);
});
