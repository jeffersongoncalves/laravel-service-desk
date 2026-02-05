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
}
