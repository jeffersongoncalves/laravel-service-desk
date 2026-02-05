<?php

namespace JeffersonGoncalves\ServiceDesk\Exceptions;

use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public static function make(TicketStatus $from, TicketStatus $to): self
    {
        return new self(
            "Cannot transition ticket status from [{$from->value}] to [{$to->value}]."
        );
    }
}
