<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class TicketStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketStatus $oldStatus,
        public readonly TicketStatus $newStatus,
        public readonly ?object $performer = null,
    ) {}
}
