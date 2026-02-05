<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class TicketPriorityChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketPriority $oldPriority,
        public readonly TicketPriority $newPriority,
        public readonly ?object $performer = null,
    ) {}
}
