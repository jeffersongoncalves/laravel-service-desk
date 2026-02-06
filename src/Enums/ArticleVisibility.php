<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum ArticleVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';

    public function label(): string
    {
        return __('service-desk::service-desk.knowledge_base.visibility.'.$this->value);
    }
}
