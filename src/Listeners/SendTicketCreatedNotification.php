<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketCreatedNotification;

class SendTicketCreatedNotification implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        if (! config('service-desk.notifications.notify_on.ticket_created', true)) {
            return;
        }

        $ticket = $event->ticket;
        $user = $ticket->user;

        if (! $user) {
            return;
        }

        $user->notify(new TicketCreatedNotification($ticket));
    }
}
