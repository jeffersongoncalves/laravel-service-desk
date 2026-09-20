<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum AutomationConditionOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case In = 'in';
    case NotIn = 'not_in';
}
