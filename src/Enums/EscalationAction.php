<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum EscalationAction: string
{
    case Notify = 'notify';
    case Reassign = 'reassign';
    case ChangePriority = 'change_priority';
    case Custom = 'custom';

    public function label(): string
    {
        return __('service-desk::service-desk.escalation.action.'.$this->value);
    }
}
