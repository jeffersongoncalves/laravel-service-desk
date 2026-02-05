<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use JeffersonGoncalves\ServiceDesk\Models\EscalationRule;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

interface EscalationHandler
{
    public function handle(EscalationRule $rule, Ticket $ticket): void;
}
