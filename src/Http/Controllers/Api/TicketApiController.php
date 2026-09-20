<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\ServiceDesk\Http\Controllers\Api\Concerns\ResolvesRemoteActor;
use JeffersonGoncalves\ServiceDesk\Http\Resources\TicketApiResource;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;

/**
 * Server side of the ticket API transport -- receives what ApiTicketTransport
 * sends. Every read/write is scoped to the calling app's own tickets
 * (VerifyServiceDeskApiSignature already stamped the verified app key onto
 * the request); a ticket that exists but belongs to a different app is
 * reported as 404, not 403, so one satellite can't even confirm another
 * satellite's ticket uuids exist.
 *
 * ponytail: show()/index() don't check the actor owns the ticket(s) beyond
 * the app-key boundary -- that's a coarser guarantee than "only your own
 * tickets" (any of this app's own users could read any of this app's
 * tickets). Add per-actor ownership here if a satellite needs that; today
 * every satellite is assumed to enforce it in its own UI.
 */
class TicketApiController
{
    use ResolvesRemoteActor;

    public function __construct(protected TicketService $tickets) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'department_id' => ['required', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
        ]);

        $data['app_key'] = $this->appKey($request);

        $ticket = $this->tickets->create($data, $this->resolveActor($request));

        return (new TicketApiResource($ticket))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $uuid): TicketApiResource
    {
        return new TicketApiResource($this->findScoped($request, $uuid));
    }

    public function showByReference(Request $request, string $reference): TicketApiResource
    {
        $ticket = $this->tickets->findByReference($reference);

        if ($ticket->app_key !== $this->appKey($request)) {
            throw TicketNotFoundException::withReference($reference);
        }

        return new TicketApiResource($ticket);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $actor = $this->resolveActor($request);

        $tickets = $this->tickets->filter($request->query())
            ->where('app_key', $this->appKey($request))
            ->where('user_type', $actor->getMorphClass())
            ->where('user_id', $actor->getKey())
            ->paginate((int) $request->query('per_page', 15));

        return TicketApiResource::collection($tickets);
    }

    public function update(Request $request, string $uuid): TicketApiResource
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'string'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $ticket = $this->findScoped($request, $uuid);
        $ticket = $this->tickets->update($ticket, $data, $this->resolveActor($request));

        return new TicketApiResource($ticket);
    }

    public function changeStatus(Request $request, string $uuid): TicketApiResource
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:open,closed'],
        ]);

        $ticket = $this->findScoped($request, $uuid);
        $ticket = $this->tickets->changeStatus($ticket, TicketStatus::from($data['status']), $this->resolveActor($request));

        return new TicketApiResource($ticket);
    }
}
