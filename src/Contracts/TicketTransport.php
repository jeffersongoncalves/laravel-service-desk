<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

/**
 * How ticket CRUD actually reaches storage. `DatabaseTicketTransport` talks
 * to Eloquent directly; a future `api` transport would instead call a
 * remote HTTP endpoint for apps that shouldn't hold DB credentials.
 * TicketService is a thin facade over whichever transport is bound --
 * callers keep using TicketService and never touch this directly.
 */
interface TicketTransport
{
    /** @param  array<string, mixed>  $data */
    public function create(array $data, Model $user): Ticket;

    /** @param  array<string, mixed>  $data */
    public function update(Ticket $ticket, array $data, ?Model $performer = null): Ticket;

    public function changeStatus(Ticket $ticket, TicketStatus $newStatus, ?Model $performer = null): Ticket;

    public function assign(Ticket $ticket, Model $operator, ?Model $assignedBy = null): Ticket;

    public function unassign(Ticket $ticket, ?Model $performer = null): Ticket;

    public function close(Ticket $ticket, ?Model $performer = null): Ticket;

    public function reopen(Ticket $ticket, ?Model $performer = null): Ticket;

    public function delete(Ticket $ticket, ?Model $performer = null): bool;

    public function findByUuid(string $uuid): Ticket;

    public function findByReference(string $reference): Ticket;
}
