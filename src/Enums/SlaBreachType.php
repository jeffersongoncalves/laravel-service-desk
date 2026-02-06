<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum SlaBreachType: string
{
    case FirstResponse = 'first_response';
    case NextResponse = 'next_response';
    case Resolution = 'resolution';

    public function label(): string
    {
        return __('service-desk::service-desk.sla.breach_type.'.$this->value);
    }
}
