<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum ServiceCategoryVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Draft = 'draft';

    public function label(): string
    {
        return __('service-desk::service-desk.service_catalog.visibility.'.$this->value);
    }
}
