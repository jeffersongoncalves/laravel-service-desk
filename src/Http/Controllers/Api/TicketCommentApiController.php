<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JeffersonGoncalves\ServiceDesk\Http\Controllers\Api\Concerns\ResolvesRemoteActor;
use JeffersonGoncalves\ServiceDesk\Http\Resources\TicketCommentApiResource;
use JeffersonGoncalves\ServiceDesk\Services\CommentService;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;

/**
 * Server side of comment support on the ticket API transport -- always adds
 * a public reply via CommentService::addReply(), same as the operator
 * console. Never routes to addNote(): internal notes stay operator-only,
 * unreachable from a satellite by construction, not by a flag.
 */
class TicketCommentApiController
{
    use ResolvesRemoteActor;

    public function __construct(
        protected TicketService $tickets,
        protected CommentService $comments,
    ) {}

    public function store(Request $request, string $uuid): JsonResponse
    {
        $ticket = $this->findScoped($request, $uuid);

        $data = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $comment = $this->comments->addReply($ticket, $this->resolveActor($request), $data['body']);

        return (new TicketCommentApiResource($comment))->response()->setStatusCode(201);
    }
}
