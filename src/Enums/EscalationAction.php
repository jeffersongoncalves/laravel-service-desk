<?php

namespace JeffersonGoncalves\ServiceDesk\Enums;

enum EscalationAction: string
{
    case Notify = 'notify';
    case Reassign = 'reassign';
    case ChangePriority = 'change_priority';
    case Custom = 'custom';
}
