<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\ServiceDesk\Models\InboundEmail;

class InboundEmailProcessed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly InboundEmail $inboundEmail,
    ) {}
}
