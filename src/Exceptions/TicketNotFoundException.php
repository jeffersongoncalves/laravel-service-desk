<?php

namespace JeffersonGoncalves\ServiceDesk\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TicketNotFoundException extends RuntimeException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 404);
    }

    public static function withId(int $id): self
    {
        return new self("Ticket with ID [{$id}] not found.");
    }

    public static function withUuid(string $uuid): self
    {
        return new self("Ticket with UUID [{$uuid}] not found.");
    }

    public static function withReference(string $reference): self
    {
        return new self("Ticket with reference [{$reference}] not found.");
    }
}
