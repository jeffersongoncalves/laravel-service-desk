<?php

namespace JeffersonGoncalves\ServiceDesk\Services\Transports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Contracts\TicketTransport;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\TicketAssigned;
use JeffersonGoncalves\ServiceDesk\Events\TicketClosed;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketDeleted;
use JeffersonGoncalves\ServiceDesk\Events\TicketPriorityChanged;
use JeffersonGoncalves\ServiceDesk\Events\TicketReopened;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Events\TicketUpdated;
use JeffersonGoncalves\ServiceDesk\Exceptions\InvalidStatusTransitionException;
use JeffersonGoncalves\ServiceDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\ServiceDesk\Exceptions\UnauthorizedOperatorException;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

/**
 * Talks to Eloquent directly -- the only transport this package shipped
 * with until a second one existed to make the interface meaningful. Moved
 * out of TicketService verbatim; behavior is unchanged.
 */
class DatabaseTicketTransport implements TicketTransport
{
    /** @param  array<string, mixed>  $data */
    public function create(array $data, Model $user): Ticket
    {
        return DB::transaction(function () use ($data, $user) {
            $ticket = new Ticket;
            $ticket->fill($data);
            $ticket->user_type = $user->getMorphClass();
            $ticket->user_id = $user->getKey();

            if (! isset($data['source'])) {
                $ticket->source = 'web';
            }

            $ticket->save();
            $ticket->load(['department', 'category']);

            event(new TicketCreated($ticket));

            return $ticket;
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(Ticket $ticket, array $data, ?Model $performer = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $data, $performer) {
            $oldStatus = $ticket->status;
            $oldPriority = $ticket->priority;

            if (array_key_exists('status', $data)) {
                $newStatus = $data['status'] instanceof TicketStatus
                    ? $data['status']
                    : TicketStatus::from($data['status']);

                if (! $oldStatus->canTransitionTo($newStatus)) {
                    throw InvalidStatusTransitionException::make($oldStatus, $newStatus);
                }

                if ($performer) {
                    $this->authorizeStatusChange($ticket, $oldStatus, $newStatus, $performer);
                }
            }

            $ticket->fill($data);
            $changes = $ticket->getDirty();
            $ticket->save();

            if (isset($changes['status']) && $oldStatus !== $ticket->status) {
                if ($oldStatus->pausesSla() && ! $ticket->status->pausesSla()) {
                    $ticket->ticketSla?->resume();
                } elseif (! $oldStatus->pausesSla() && $ticket->status->pausesSla()) {
                    $ticket->ticketSla?->pause();
                }

                event(new TicketStatusChanged($ticket, $oldStatus, $ticket->status, $performer));

                if ($ticket->status === TicketStatus::Closed) {
                    $ticket->update(['closed_at' => now()]);
                    event(new TicketClosed($ticket, $performer));
                }

                if ($oldStatus === TicketStatus::Closed && $ticket->status === TicketStatus::Open) {
                    $ticket->update(['closed_at' => null]);
                    event(new TicketReopened($ticket, $performer));
                }
            }

            if (isset($changes['priority']) && $oldPriority !== $ticket->priority) {
                event(new TicketPriorityChanged($ticket, $oldPriority, $ticket->priority, $performer));
            }

            event(new TicketUpdated($ticket, $changes, $performer));

            return $ticket->fresh() ?? $ticket;
        });
    }

    public function changeStatus(Ticket $ticket, TicketStatus $newStatus, ?Model $performer = null): Ticket
    {
        return $this->update($ticket, ['status' => $newStatus], $performer);
    }

    public function assign(Ticket $ticket, Model $operator, ?Model $assignedBy = null): Ticket
    {
        $ticket->assigned_to_type = $operator->getMorphClass();
        $ticket->assigned_to_id = $operator->getKey();
        $ticket->save();

        event(new TicketAssigned($ticket, $operator, $assignedBy));

        return $ticket;
    }

    public function unassign(Ticket $ticket, ?Model $performer = null): Ticket
    {
        $ticket->assigned_to_type = null;
        $ticket->assigned_to_id = null;
        $ticket->save();

        event(new TicketUpdated($ticket, ['assigned_to_id' => null], $performer));

        return $ticket;
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
        event(new TicketDeleted($ticket, $performer));

        return (bool) $ticket->delete();
    }

    public function findByUuid(string $uuid): Ticket
    {
        // Postgres's native uuid column rejects a malformed literal with a raw
        // SQL error instead of just finding no rows, so validate the format first.
        $ticket = Str::isUuid($uuid) ? Ticket::where('uuid', $uuid)->first() : null;

        if (! $ticket) {
            throw TicketNotFoundException::withUuid($uuid);
        }

        return $ticket;
    }

    public function findByReference(string $reference): Ticket
    {
        $ticket = Ticket::where('reference_number', $reference)->first();

        if (! $ticket) {
            throw TicketNotFoundException::withReference($reference);
        }

        return $ticket;
    }

    /**
     * Enforce that only an operator of the ticket's department -- or the
     * ticket's own requester closing/reopening their own ticket -- may
     * change status. The package's own service layer is the only place
     * this is checked centrally; without it, an API/UI caller is the only
     * thing standing between a requester and an operator-only transition.
     */
    protected function authorizeStatusChange(Ticket $ticket, TicketStatus $oldStatus, TicketStatus $newStatus, Model $performer): void
    {
        if ($this->isOperatorForTicket($ticket, $performer)) {
            return;
        }

        $isOwnTicket = $ticket->user_type === $performer->getMorphClass()
            && $ticket->user_id === $performer->getKey();

        $isRequesterAllowedTransition = $newStatus === TicketStatus::Closed
            || ($oldStatus === TicketStatus::Closed && $newStatus === TicketStatus::Open);

        if (! $isOwnTicket || ! $isRequesterAllowedTransition) {
            throw UnauthorizedOperatorException::forTicket($ticket->id);
        }
    }

    protected function isOperatorForTicket(Ticket $ticket, Model $performer): bool
    {
        return DB::table('service_desk_department_operator')
            ->where('department_id', $ticket->department_id)
            ->where('operator_type', $performer->getMorphClass())
            ->where('operator_id', $performer->getKey())
            ->exists();
    }
}
