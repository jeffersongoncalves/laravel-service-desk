<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('service-desk::service-desk.service_catalog.approval_status.'.$this->value);
    }
}
