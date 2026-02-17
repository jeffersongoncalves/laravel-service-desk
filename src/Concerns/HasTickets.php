<?php

namespace JeffersonGoncalves\ServiceDesk\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Models\TicketWatcher;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Ticket> $serviceDeskTickets
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TicketComment> $serviceDeskComments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TicketWatcher> $serviceDeskWatching
 */
trait HasTickets
{
    /** @return MorphMany<Ticket, $this> */
    public function serviceDeskTickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'user');
    }

    /** @return MorphMany<TicketComment, $this> */
    public function serviceDeskComments(): MorphMany
    {
        return $this->morphMany(TicketComment::class, 'author');
    }

    /** @return MorphMany<TicketWatcher, $this> */
    public function serviceDeskWatching(): MorphMany
    {
        return $this->morphMany(TicketWatcher::class, 'watcher');
    }
}
