<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum AutomationAction: string
{
    case Reassign = 'reassign';
    case ChangePriority = 'change_priority';
    case ChangeStatus = 'change_status';
    case AddTag = 'add_tag';

    public function label(): string
    {
        return __('service-desk::service-desk.automation.action.'.$this->value);
    }
}
