<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return __('service-desk::service-desk.knowledge_base.article_status.'.$this->value);
    }
}
