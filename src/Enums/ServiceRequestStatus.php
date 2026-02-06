<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum ServiceRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case InProgress = 'in_progress';
    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('service-desk::service-desk.service_catalog.request_status.'.$this->value);
    }
}
