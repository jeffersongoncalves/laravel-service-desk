<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum ServiceCategoryVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Draft = 'draft';
}
