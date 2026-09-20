<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationTrigger;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Services\AutomationService;

class RunAutomationRules implements ShouldQueue
{
    public function __construct(
        protected AutomationService $automationService,
    ) {}

    public function handleTicketCreated(TicketCreated $event): void
    {
        $this->automationService->evaluate($event->ticket, AutomationTrigger::TicketCreated);
    }

    public function handleTicketStatusChanged(TicketStatusChanged $event): void
    {
        $this->automationService->evaluate($event->ticket, AutomationTrigger::TicketStatusChanged);
    }

    public function handleCommentAdded(CommentAdded $event): void
    {
        $this->automationService->evaluate($event->ticket, AutomationTrigger::CommentAdded);
    }

    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            TicketCreated::class => 'handleTicketCreated',
            TicketStatusChanged::class => 'handleTicketStatusChanged',
            CommentAdded::class => 'handleCommentAdded',
        ];
    }
}
