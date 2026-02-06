<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class SlaNearBreachNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $breachType,
        public readonly int $minutesRemaining,
    ) {
        $this->queue = config('service-desk.notifications.queue', 'default');
    }

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return config('service-desk.notifications.channels', ['mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->ticket;
        $subject = str_replace(
            ':reference',
            $ticket->reference_number,
            config('service-desk.email.subject_prefix', '[Service Desk #:reference]')
        );

        return (new MailMessage)
            ->subject($subject.' '.__('service-desk::notifications.sla_near_breach.subject'))
            ->greeting(__('service-desk::notifications.sla_near_breach.greeting'))
            ->line(__('service-desk::notifications.sla_near_breach.body', [
                'reference' => $ticket->reference_number,
                'title' => $ticket->title,
                'breach_type' => $this->breachType,
                'minutes' => $this->minutesRemaining,
            ]))
            ->line(__('service-desk::notifications.sla_near_breach.priority', [
                'priority' => $ticket->priority->label(),
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_uuid' => $this->ticket->uuid,
            'reference_number' => $this->ticket->reference_number,
            'title' => $this->ticket->title,
            'breach_type' => $this->breachType,
            'minutes_remaining' => $this->minutesRemaining,
            'type' => 'sla_near_breach',
        ];
    }
}
