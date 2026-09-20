<?php

namespace JeffersonGoncalves\ServiceDesk\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class UnauthorizedOperatorException extends RuntimeException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 403);
    }

    public static function forDepartment(int $departmentId): self
    {
        return new self("Operator is not authorized for department [{$departmentId}].");
    }

    public static function forTicket(int $ticketId): self
    {
        return new self("Operator is not authorized to manage ticket [{$ticketId}].");
    }

    /**
     * For contexts with no local numeric ticket id -- e.g. a ticket hydrated
     * from the API transport, which only carries a uuid.
     */
    public static function forTicketUuid(string $uuid): self
    {
        return new self("Operator is not authorized to manage ticket [{$uuid}].");
    }
}
