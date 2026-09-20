<?php

namespace JeffersonGoncalves\ServiceDesk\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }

    public static function make(TicketStatus $from, TicketStatus $to): self
    {
        return new self(
            "Cannot transition ticket status from [{$from->value}] to [{$to->value}]."
        );
    }
}
