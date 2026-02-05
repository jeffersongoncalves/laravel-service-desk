<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum SlaBreachType: string
{
    case FirstResponse = 'first_response';
    case NextResponse = 'next_response';
    case Resolution = 'resolution';
}
