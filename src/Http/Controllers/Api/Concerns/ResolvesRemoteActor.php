<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JeffersonGoncalves\ServiceDesk\Api\RemoteActor;
use JeffersonGoncalves\ServiceDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;

/**
 * Shared by every ticket-API controller: resolving the caller's asserted
 * actor (checked against that app's allow-list) and finding a ticket while
 * enforcing it belongs to the calling app. Identical in both controllers
 * before this was extracted -- kept as one implementation since it's a
 * security check, not incidental duplication.
 */
trait ResolvesRemoteActor
{
    /** Requires a `protected TicketService $tickets` property on the using class. */
    protected function findScoped(Request $request, string $uuid): Ticket
    {
        /** @var TicketService $tickets */
        $tickets = $this->tickets;
        $ticket = $tickets->findByUuid($uuid);

        if ($ticket->app_key !== $this->appKey($request)) {
            throw TicketNotFoundException::withUuid($uuid);
        }

        return $ticket;
    }

    protected function resolveActor(Request $request): RemoteActor
    {
        $data = $request->validate([
            'actor' => ['required', 'array'],
            'actor.type' => ['required', 'string'],
            'actor.id' => ['required'],
            'actor.name' => ['nullable', 'string'],
            'actor.email' => ['nullable', 'email'],
        ]);

        $appKey = (string) $request->attributes->get('service_desk_api_app_key');
        $allowedTypes = config("service-desk.api.clients.{$appKey}.actor_types", []);

        if (! in_array($data['actor']['type'], $allowedTypes, true)) {
            throw ValidationException::withMessages([
                'actor.type' => ['This app is not allowed to assert that actor type.'],
            ]);
        }

        return new RemoteActor($data['actor']);
    }

    protected function appKey(Request $request): string
    {
        return (string) $request->attributes->get('service_desk_api_app_key');
    }
}
