<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Contracts\TicketTransport;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
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

    /**
     * Filter/search/sort tickets for a listing endpoint. Callers get back a
     * builder to paginate or further constrain (e.g. scope to a department);
     * `status`/`priority`/`sort`/`direction` are validated against an
     * allow-list rather than passed through, and `search` is escaped for
     * the LIKE wildcards so a literal `%`/`_` in the query can't widen the match.
     *
     * @param  array{status?: string, priority?: string, search?: string, sort?: string, direction?: string}  $filters
     * @return Builder<Ticket>
     */
    public function filter(array $filters = []): Builder
    {
        $query = Ticket::query();

        if (! empty($filters['status']) && $status = TicketStatus::tryFrom($filters['status'])) {
            $query->where('status', $status);
        }

        if (! empty($filters['priority']) && $priority = TicketPriority::tryFrom($filters['priority'])) {
            $query->where('priority', $priority);
        }

        if (! empty($filters['search'])) {
            // SQLite's LIKE has no default escape character (unlike MySQL/Postgres),
            // and backslash-as-escape-char is itself inconsistent across dialects'
            // string literal parsing -- '!' is inert everywhere, so it's used
            // instead and declared explicitly via ESCAPE.
            $search = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']);
            $like = "%{$search}%";

            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw("title LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("reference_number LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("description LIKE ? ESCAPE '!'", [$like]);
            });
        }

        $sortable = ['created_at', 'updated_at', 'due_at', 'title', 'status', 'priority'];
        $sort = in_array($filters['sort'] ?? null, $sortable, true) ? $filters['sort'] : 'created_at';
        $direction = strtolower($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($direction === 'asc') {
            $orderRaw = match ($sort) {
                'status' => "CASE status WHEN 'open' THEN 1 WHEN 'pending' THEN 2 WHEN 'in_progress' THEN 3 WHEN 'on_hold' THEN 4 WHEN 'resolved' THEN 5 WHEN 'closed' THEN 6 END asc",
                'priority' => "CASE priority WHEN 'low' THEN 1 WHEN 'medium' THEN 2 WHEN 'high' THEN 3 WHEN 'urgent' THEN 4 END asc",
                default => null,
            };
        } else {
            $orderRaw = match ($sort) {
                'status' => "CASE status WHEN 'open' THEN 1 WHEN 'pending' THEN 2 WHEN 'in_progress' THEN 3 WHEN 'on_hold' THEN 4 WHEN 'resolved' THEN 5 WHEN 'closed' THEN 6 END desc",
                'priority' => "CASE priority WHEN 'low' THEN 1 WHEN 'medium' THEN 2 WHEN 'high' THEN 3 WHEN 'urgent' THEN 4 END desc",
                default => null,
            };
        }

        // Case orderings above mirror TicketStatus::cases() / TicketPriority::cases()
        // declaration order -- update both if either enum's cases change.
        if ($orderRaw !== null) {
            $query->orderByRaw($orderRaw);
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query;
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
