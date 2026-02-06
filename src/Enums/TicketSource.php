<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum TicketSource: string
{
    case Web = 'web';
    case Email = 'email';
    case Api = 'api';
    case ServiceRequest = 'service_request';
    case Phone = 'phone';
    case Chat = 'chat';

    public function label(): string
    {
        return __('service-desk::service-desk.source.'.$this->value);
    }
}
