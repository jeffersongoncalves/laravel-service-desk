<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketUpdated;
use JeffersonGoncalves\ServiceDesk\Services\KnowledgeBaseService;

class SuggestKbArticles implements ShouldQueue
{
    public function __construct(
        protected KnowledgeBaseService $knowledgeBaseService,
    ) {}

    public function handleTicketCreated(TicketCreated $event): void
    {
        $this->knowledgeBaseService->updateSuggestedArticles($event->ticket);
    }

    public function handleTicketUpdated(TicketUpdated $event): void
    {
        if (! array_key_exists('title', $event->changes) && ! array_key_exists('description', $event->changes)) {
            return;
        }

        $this->knowledgeBaseService->updateSuggestedArticles($event->ticket);
    }

    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            TicketCreated::class => 'handleTicketCreated',
            TicketUpdated::class => 'handleTicketUpdated',
        ];
    }
}
