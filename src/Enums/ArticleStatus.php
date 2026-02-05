<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
