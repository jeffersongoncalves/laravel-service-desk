<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use JeffersonGoncalves\ServiceDesk\Events\TicketAssigned;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketAssignedNotification;

class SendTicketAssignedNotification implements ShouldQueue
{
    public function handle(TicketAssigned $event): void
    {
        if (! config('service-desk.notifications.notify_on.ticket_assigned', true)) {
            return;
        }

        $assignedTo = $event->assignedTo;

        if (! $assignedTo) {
            return;
        }

        $assignedTo->notify(new TicketAssignedNotification($event->ticket));
    }
}
