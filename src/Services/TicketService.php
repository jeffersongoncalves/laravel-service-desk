<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Contracts\TicketTransport;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

/**
 * Thin facade over whichever TicketTransport is bound (config
 * `service-desk.ticket.transport`, default `database`). Consumers keep
 * calling TicketService exactly as before -- swapping transports doesn't
 * change this class's public API.
 */
class TicketService
{
    public function __construct(
        protected TicketTransport $transport,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(array $data, Model $user): Ticket
    {
        return $this->transport->create($data, $user);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Ticket $ticket, array $data, ?Model $performer = null): Ticket
    {
        return $this->transport->update($ticket, $data, $performer);
    }

    public function changeStatus(Ticket $ticket, TicketStatus $newStatus, ?Model $performer = null): Ticket
    {
        return $this->transport->changeStatus($ticket, $newStatus, $performer);
    }

    public function assign(Ticket $ticket, Model $operator, ?Model $assignedBy = null): Ticket
    {
        return $this->transport->assign($ticket, $operator, $assignedBy);
    }

    public function unassign(Ticket $ticket, ?Model $performer = null): Ticket
    {
        return $this->transport->unassign($ticket, $performer);
    }

    public function close(Ticket $ticket, ?Model $performer = null): Ticket
    {
        return $this->transport->close($ticket, $performer);
    }

    public function reopen(Ticket $ticket, ?Model $performer = null): Ticket
    {
        return $this->transport->reopen($ticket, $performer);
    }

    public function delete(Ticket $ticket, ?Model $performer = null): bool
    {
        return $this->transport->delete($ticket, $performer);
    }

    public function findByUuid(string $uuid): Ticket
    {
        return $this->transport->findByUuid($uuid);
    }

    public function findByReference(string $reference): Ticket
    {
        return $this->transport->findByReference($reference);
    }

    /**
     * Watcher management stays local to TicketService rather than moving
     * behind the transport contract -- it isn't part of the dual-transport
     * surface (help-desk's sibling API driver never had watcher parity
     * either), and going through the database directly here is fine
     * whichever transport handles the ticket's own CRUD.
     */
    public function addWatcher(Ticket $ticket, Model $watcher): void
    {
        $ticket->watchers()->firstOrCreate([
            'watcher_type' => $watcher->getMorphClass(),
            'watcher_id' => $watcher->getKey(),
        ]);
    }

    public function removeWatcher(Ticket $ticket, Model $watcher): void
    {
        $ticket->watchers()
            ->where('watcher_type', $watcher->getMorphClass())
            ->where('watcher_id', $watcher->getKey())
            ->delete();
    }
}
