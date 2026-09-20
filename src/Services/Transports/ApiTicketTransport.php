<?php

namespace JeffersonGoncalves\ServiceDesk\Services\Transports;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskApiClient;
use JeffersonGoncalves\ServiceDesk\Contracts\TicketTransport;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Exceptions\InvalidStatusTransitionException;
use JeffersonGoncalves\ServiceDesk\Exceptions\ServiceDeskApiException;
use JeffersonGoncalves\ServiceDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

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
            throw $e->status === 404 ? TicketNotFoundException::withUuid($ticket->uuid) : $e;
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
            if ($e->status === 404) {
                throw TicketNotFoundException::withUuid($ticket->uuid);
            }

            if ($e->status === 409) {
                throw InvalidStatusTransitionException::make($ticket->status, $newStatus);
            }

            throw $e;
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
     * @param  array<string, mixed>  $attributes
     */
    protected function hydrate(array $attributes): Ticket
    {
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
