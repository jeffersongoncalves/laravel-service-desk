<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\TicketFeedbackReceived;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\FeedbackService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->service = app(FeedbackService::class);
    $this->requester = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);
});

it('submits feedback with a rating and comment', function () {
    Event::fake([TicketFeedbackReceived::class]);

    $ticket = Ticket::factory()->status(TicketStatus::Resolved)->create();

    $feedback = $this->service->submit($ticket, 5, $this->requester, 'Great support!');

    expect($feedback->rating)->toBe(5)
        ->and($feedback->comment)->toBe('Great support!')
        ->and($feedback->user_id)->toBe($this->requester->id)
        ->and($feedback->user_type)->toBe($this->requester->getMorphClass())
        ->and($ticket->feedback()->count())->toBe(1);

    Event::assertDispatched(TicketFeedbackReceived::class, function ($event) use ($ticket, $feedback) {
        return $event->ticket->id === $ticket->id && $event->feedback->id === $feedback->id;
    });
});

it('accepts feedback with no user attached', function () {
    $ticket = Ticket::factory()->status(TicketStatus::Resolved)->create();

    $feedback = $this->service->submit($ticket, 4);

    expect($feedback->user_id)->toBeNull()
        ->and($feedback->user_type)->toBeNull();
});

it('rejects a rating outside 1-5', function () {
    $ticket = Ticket::factory()->create();

    $this->service->submit($ticket, 6);
})->throws(InvalidArgumentException::class);

it('rejects a rating below 1', function () {
    $ticket = Ticket::factory()->create();

    $this->service->submit($ticket, 0);
})->throws(InvalidArgumentException::class);

it('auto-reopens a resolved ticket when the rating is below the configured threshold', function () {
    config()->set('service-desk.csat.auto_reopen_below_rating', 3);

    $ticket = Ticket::factory()->status(TicketStatus::Resolved)->create();

    $this->service->submit($ticket, 2);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
});

it('auto-reopens a closed ticket when the rating is below the configured threshold', function () {
    config()->set('service-desk.csat.auto_reopen_below_rating', 3);
    config()->set('service-desk.ticket.allow_reopen', true);

    $ticket = Ticket::factory()->status(TicketStatus::Closed)->create();

    $this->service->submit($ticket, 1);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
});

it('does not reopen when the rating meets the threshold', function () {
    config()->set('service-desk.csat.auto_reopen_below_rating', 3);

    $ticket = Ticket::factory()->status(TicketStatus::Resolved)->create();

    $this->service->submit($ticket, 3);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

it('does not reopen when auto_reopen_below_rating is null', function () {
    config()->set('service-desk.csat.auto_reopen_below_rating', null);

    $ticket = Ticket::factory()->status(TicketStatus::Resolved)->create();

    $this->service->submit($ticket, 1);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

it('does not crash when auto-reopen is triggered but allow_reopen is disabled', function () {
    config()->set('service-desk.csat.auto_reopen_below_rating', 3);
    config()->set('service-desk.ticket.allow_reopen', false);

    $ticket = Ticket::factory()->status(TicketStatus::Closed)->create();

    $feedback = $this->service->submit($ticket, 1);

    expect($feedback->rating)->toBe(1)
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Closed);
});
