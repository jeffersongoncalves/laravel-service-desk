<?php

namespace JeffersonGoncalves\ServiceDesk\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Models\TicketWatcher;

trait HasTickets
{
    public function serviceDeskTickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'user');
    }

    public function serviceDeskComments(): MorphMany
    {
        return $this->morphMany(TicketComment::class, 'author');
    }

    public function serviceDeskWatching(): MorphMany
    {
        return $this->morphMany(TicketWatcher::class, 'watcher');
    }
}
