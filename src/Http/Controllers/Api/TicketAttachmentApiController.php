<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use JeffersonGoncalves\ServiceDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\ServiceDesk\Http\Controllers\Api\Concerns\ResolvesRemoteActor;
use JeffersonGoncalves\ServiceDesk\Http\Resources\TicketAttachmentApiResource;
use JeffersonGoncalves\ServiceDesk\Models\TicketAttachment;
use JeffersonGoncalves\ServiceDesk\Services\AttachmentService;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;

/**
 * Attachments aren't part of the TicketTransport contract (AttachmentService
 * always talks to local storage, which a satellite app has no access to),
 * so this is a separate, additive surface: a satellite sends a file as
 * base64 in JSON, this app decodes and stores it against a ticket it
 * already owns.
 */
class TicketAttachmentApiController
{
    use ResolvesRemoteActor;

    public function __construct(
        protected TicketService $tickets,
        protected AttachmentService $attachments,
    ) {}

    public function store(Request $request, string $uuid): JsonResponse
    {
        $ticket = $this->findScoped($request, $uuid);

        $data = $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:255'],
            'contents' => ['required', 'string'],
        ]);

        $contents = base64_decode($data['contents'], true);

        if ($contents === false) {
            throw ValidationException::withMessages(['contents' => ['Must be valid base64.']]);
        }

        $maxKb = (int) config('service-desk.api.max_inline_attachment', 2048);

        if (ceil(strlen($contents) / 1024) > $maxKb) {
            throw ValidationException::withMessages(['contents' => ["Must not exceed {$maxKb} KB decoded."]]);
        }

        $attachment = $this->attachments->storeFromContents(
            $ticket,
            $contents,
            $data['file_name'],
            $data['mime_type'],
            $this->resolveActor($request),
        );

        return (new TicketAttachmentApiResource($attachment))->response()->setStatusCode(201);
    }

    public function index(Request $request, string $uuid): AnonymousResourceCollection
    {
        $ticket = $this->findScoped($request, $uuid);

        return TicketAttachmentApiResource::collection($ticket->attachments);
    }

    public function show(Request $request, string $uuid, string $attachmentUuid): TicketAttachmentApiResource
    {
        $ticket = $this->findScoped($request, $uuid);

        $attachment = TicketAttachment::where('ticket_id', $ticket->id)
            ->where('uuid', $attachmentUuid)
            ->first();

        if (! $attachment) {
            throw TicketNotFoundException::withUuid($attachmentUuid);
        }

        return (new TicketAttachmentApiResource($attachment))->withContents();
    }
}
