<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum AutomationTrigger: string
{
    case TicketCreated = 'ticket_created';
    case TicketStatusChanged = 'ticket_status_changed';
    case CommentAdded = 'comment_added';

    public function label(): string
    {
        return __('service-desk::service-desk.automation.trigger.'.$this->value);
    }
}
