<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use JeffersonGoncalves\ServiceDesk\Events\TicketClosed;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketClosedNotification;

class SendTicketClosedNotification implements ShouldQueue
{
    public function handle(TicketClosed $event): void
    {
        if (! config('service-desk.notifications.notify_on.ticket_closed', true)) {
            return;
        }

        $user = $event->ticket->resolvedUser();

        if ($user !== null && method_exists($user, 'notify')) {
            $user->notify(new TicketClosedNotification($event->ticket));
        }
    }
}
