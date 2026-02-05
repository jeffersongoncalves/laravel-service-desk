<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketStatus $oldStatus,
        public readonly TicketStatus $newStatus,
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
            ->subject($subject.' '.__('service-desk::notifications.status_changed.subject'))
            ->greeting(__('service-desk::notifications.status_changed.greeting'))
            ->line(__('service-desk::notifications.status_changed.body', [
                'reference' => $ticket->reference_number,
                'title' => $ticket->title,
            ]))
            ->line(__('service-desk::notifications.status_changed.transition', [
                'old' => $this->oldStatus->label(),
                'new' => $this->newStatus->label(),
            ]))
            ->withSymfonyMessage(function ($message) use ($ticket) {
                $domain = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost';
                $messageId = "<{$ticket->uuid}-status-changed-{$ticket->id}@{$domain}>";

                $headers = $message->getHeaders();
                $headers->addTextHeader('X-ServiceDesk-Ticket-Ref', $ticket->reference_number);
                $headers->addTextHeader('Message-ID', $messageId);
                $headers->addTextHeader('In-Reply-To', "<{$ticket->uuid}-created-{$ticket->id}@{$domain}>");
                $headers->addTextHeader('References', "<{$ticket->uuid}-created-{$ticket->id}@{$domain}>");
            });
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
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
            'type' => 'ticket_status_changed',
        ];
    }
}
