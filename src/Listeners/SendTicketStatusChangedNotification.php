<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Notifications\TicketStatusChangedNotification;

class SendTicketStatusChangedNotification implements ShouldQueue
{
    public function handle(TicketStatusChanged $event): void
    {
        if (! config('service-desk.notifications.notify_on.ticket_status_changed', true)) {
            return;
        }

        $ticket = $event->ticket;
        $user = $ticket->user;

        if (! $user) {
            return;
        }

        if (method_exists($user, 'notify')) {
            $user->notify(new TicketStatusChangedNotification(
                $ticket,
                $event->oldStatus,
                $event->newStatus,
            ));
        }
    }
}
