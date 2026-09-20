<?php

namespace JeffersonGoncalves\ServiceDesk\Services\Transports;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskApiClient;
use JeffersonGoncalves\ServiceDesk\Contracts\TicketTransport;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Exceptions\InvalidStatusTransitionException;
use JeffersonGoncalves\ServiceDesk\Exceptions\ServiceDeskApiException;
use JeffersonGoncalves\ServiceDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\ServiceDesk\Exceptions\UnauthorizedOperatorException;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use Throwable;

/**
 * Talks to a central service-desk instance over HTTP instead of a shared
 * database -- for satellite apps that shouldn't hold ticket DB credentials.
 * Only requester-facing actions have an endpoint: assign/unassign/delete are
 * operator-console actions that make no sense from a satellite and are
 * refused before any request is sent; changeStatus only allows close/reopen,
 * the two self-service transitions a requester can make -- the central
 * instance still runs the real authorization/transition logic (the same
 * DatabaseTicketTransport code) on its own side, this is just a narrower
 * client-side surface mirroring which endpoints actually exist.
 */
class ApiTicketTransport implements TicketTransport
{
    public function __construct(protected ServiceDeskApiClient $client) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, Model $user): Ticket
    {
        $response = $this->client->post('tickets', [
            ...$data,
            'actor' => $this->actorPayload($user),
        ]);

        return $this->hydrate($this->unwrap($response));
    }

    /** @param  array<string, mixed>  $data */
    public function update(Ticket $ticket, array $data, ?Model $performer = null): Ticket
    {
        if (array_key_exists('status', $data)) {
            $newStatus = $data['status'] instanceof TicketStatus
                ? $data['status']
                : TicketStatus::from($data['status']);

            $ticket = $this->changeStatus($ticket, $newStatus, $performer);
            $data = array_diff_key($data, ['status' => true]);

            if ($data === []) {
                return $ticket;
            }
        }

        try {
            $response = $this->client->patch("tickets/{$ticket->uuid}", [
                ...$data,
                'actor' => $performer ? $this->actorPayload($performer) : null,
            ]);
        } catch (ServiceDeskApiException $e) {
            throw $this->remap($e, $ticket);
        }

        return $this->hydrate($this->unwrap($response));
    }

    public function changeStatus(Ticket $ticket, TicketStatus $newStatus, ?Model $performer = null): Ticket
    {
        if (! in_array($newStatus, [TicketStatus::Closed, TicketStatus::Open], true)) {
            throw ServiceDeskApiException::statusNotSupported($newStatus);
        }

        try {
            $response = $this->client->post("tickets/{$ticket->uuid}/status", [
                'status' => $newStatus->value,
                'actor' => $performer ? $this->actorPayload($performer) : null,
            ]);
        } catch (ServiceDeskApiException $e) {
            throw $this->remap($e, $ticket, $newStatus);
        }

        return $this->hydrate($this->unwrap($response));
    }

    public function assign(Ticket $ticket, Model $operator, ?Model $assignedBy = null): Ticket
    {
        throw ServiceDeskApiException::operatorOnly('assign');
    }

    public function unassign(Ticket $ticket, ?Model $performer = null): Ticket
    {
        throw ServiceDeskApiException::operatorOnly('unassign');
    }

    public function close(Ticket $ticket, ?Model $performer = null): Ticket
    {
        return $this->changeStatus($ticket, TicketStatus::Closed, $performer);
    }

    public function reopen(Ticket $ticket, ?Model $performer = null): Ticket
    {
        return $this->changeStatus($ticket, TicketStatus::Open, $performer);
    }

    public function delete(Ticket $ticket, ?Model $performer = null): bool
    {
        throw ServiceDeskApiException::operatorOnly('delete');
    }

    /**
     * Uploads a file as base64 to a ticket on the central instance. Not part
     * of TicketTransport -- attachments have no equivalent on the database
     * transport's own interface, they're handled by AttachmentService there.
     *
     * @return array<string, mixed> the created attachment's resource data
     */
    public function uploadAttachment(Ticket $ticket, string $fileName, string $mimeType, string $contents, Model $performer): array
    {
        try {
            $response = $this->client->post("tickets/{$ticket->uuid}/attachments", [
                'file_name' => $fileName,
                'mime_type' => $mimeType,
                'contents' => base64_encode($contents),
                'actor' => $this->actorPayload($performer),
            ]);
        } catch (ServiceDeskApiException $e) {
            throw $this->remap($e, $ticket);
        }

        return $this->unwrap($response);
    }

    /** @return array<int, array<string, mixed>> */
    public function listAttachments(Ticket $ticket): array
    {
        try {
            $response = $this->client->get("tickets/{$ticket->uuid}/attachments");
        } catch (ServiceDeskApiException $e) {
            throw $this->remap($e, $ticket);
        }

        return $response['data'] ?? [];
    }

    /** Returns the attachment's raw (decoded) contents. */
    public function downloadAttachment(Ticket $ticket, string $attachmentUuid): string
    {
        try {
            $response = $this->client->get("tickets/{$ticket->uuid}/attachments/{$attachmentUuid}");
        } catch (ServiceDeskApiException $e) {
            throw $this->remap($e, $ticket);
        }

        $contents = $this->unwrap($response)['contents'] ?? '';

        return base64_decode((string) $contents, true) ?: '';
    }

    public function findByUuid(string $uuid): Ticket
    {
        try {
            $response = $this->client->get("tickets/{$uuid}");
        } catch (ServiceDeskApiException $e) {
            throw $e->status === 404 ? TicketNotFoundException::withUuid($uuid) : $e;
        }

        return $this->hydrate($this->unwrap($response));
    }

    public function findByReference(string $reference): Ticket
    {
        try {
            $response = $this->client->get("tickets/by-reference/{$reference}");
        } catch (ServiceDeskApiException $e) {
            throw $e->status === 404 ? TicketNotFoundException::withReference($reference) : $e;
        }

        return $this->hydrate($this->unwrap($response));
    }

    /**
     * Remaps a transport-level failure to the same exception type the
     * database transport throws for the equivalent failure, using whatever
     * local context (ticket, attempted status) this call site has. Anything
     * without a known business meaning (5xx, unreachable, etc.) passes
     * through as-is.
     */
    protected function remap(ServiceDeskApiException $e, Ticket $ticket, ?TicketStatus $attemptedStatus = null): Throwable
    {
        return match ($e->status) {
            404 => TicketNotFoundException::withUuid($ticket->uuid),
            403 => UnauthorizedOperatorException::forTicketUuid($ticket->uuid),
            409 => $attemptedStatus !== null
                ? InvalidStatusTransitionException::make($ticket->status, $attemptedStatus)
                : $e,
            default => $e,
        };
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function unwrap(array $response): array
    {
        $data = $response['data'] ?? $response;

        return is_array($data) ? $data : [];
    }

    /**
     * Builds a Ticket instance from API response attributes -- it "exists"
     * (remotely, on the central instance) but was never inserted locally, so
     * save()-ing it by accident would attempt an update against a row this
     * app's database doesn't have. Callers are expected to only mutate it
     * through this transport, same as with the database transport.
     *
     * TicketApiResource never exposes the central app's own user_type/user_id
     * (a satellite has no use for the central app's internal morph identity)
     * or the auto-increment id, so those stay unset on the hydrated ticket;
     * requester_name/email map onto the same user_name/user_email columns
     * the database transport snapshots onto a real row.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function hydrate(array $attributes): Ticket
    {
        if (array_key_exists('requester_name', $attributes)) {
            $attributes['user_name'] = $attributes['requester_name'];
            unset($attributes['requester_name']);
        }

        if (array_key_exists('requester_email', $attributes)) {
            $attributes['user_email'] = $attributes['requester_email'];
            unset($attributes['requester_email']);
        }

        $ticket = new Ticket;
        $ticket->forceFill($attributes);
        $ticket->exists = true;
        $ticket->syncOriginal();

        return $ticket;
    }

    /** @return array<string, mixed> */
    protected function actorPayload(Model $actor): array
    {
        return [
            'type' => $actor->getMorphClass(),
            'id' => $actor->getKey(),
            'name' => $actor->getAttribute('name'),
            'email' => $actor->getAttribute('email'),
        ];
    }
}
