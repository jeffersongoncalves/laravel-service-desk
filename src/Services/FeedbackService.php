<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\TicketFeedbackReceived;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketFeedback;

class FeedbackService
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}

    public function submit(Ticket $ticket, int $rating, ?Model $user = null, ?string $comment = null): TicketFeedback
    {
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException("Rating must be between 1 and 5, got [{$rating}].");
        }

        /** @var TicketFeedback $feedback */
        $feedback = $ticket->feedback()->create([
            'user_type' => $user?->getMorphClass(),
            'user_id' => $user?->getKey(),
            'rating' => $rating,
            'comment' => $comment,
            'created_at' => now(),
        ]);

        event(new TicketFeedbackReceived($ticket, $feedback));

        $this->maybeReopen($ticket, $rating);

        return $feedback;
    }

    protected function maybeReopen(Ticket $ticket, int $rating): void
    {
        $threshold = config('service-desk.csat.auto_reopen_below_rating');

        if ($threshold === null || $rating >= $threshold) {
            return;
        }

        if (! in_array($ticket->status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            return;
        }

        // Guard rather than let a disabled allow_reopen config (Closed's
        // allowedTransitions() becomes empty) throw out of feedback
        // submission -- a bad rating shouldn't crash the whole request.
        if (! $ticket->status->canTransitionTo(TicketStatus::Open)) {
            return;
        }

        $this->ticketService->reopen($ticket);
    }
}
