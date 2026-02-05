<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;

class ServiceRequestStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly ServiceRequestStatus $oldStatus,
        public readonly ServiceRequestStatus $newStatus,
    ) {}
}
