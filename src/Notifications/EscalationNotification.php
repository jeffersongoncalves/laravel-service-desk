<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use JeffersonGoncalves\ServiceDesk\Models\EscalationRule;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class EscalationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly EscalationRule $escalationRule,
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
            ->subject($subject.' '.__('service-desk::notifications.escalation.subject'))
            ->greeting(__('service-desk::notifications.escalation.greeting'))
            ->line(__('service-desk::notifications.escalation.body', [
                'reference' => $ticket->reference_number,
                'title' => $ticket->title,
                'breach_type' => $this->escalationRule->breach_type->value,
                'action' => $this->escalationRule->action->value,
            ]))
            ->line(__('service-desk::notifications.escalation.priority', [
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
            'escalation_rule_id' => $this->escalationRule->id,
            'breach_type' => $this->escalationRule->breach_type->value,
            'action' => $this->escalationRule->action->value,
            'type' => 'escalation',
        ];
    }
}
