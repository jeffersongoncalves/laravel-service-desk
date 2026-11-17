<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;

/**
 * Tells an operator that the requester opened a ticket, or replied to one when
 * a comment is given.
 */
class OperatorTicketActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?TicketComment $comment = null,
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
        $key = $this->comment === null ? 'operator_new_ticket' : 'operator_requester_reply';
        $subject = str_replace(
            ':reference',
            $ticket->reference_number,
            config('service-desk.email.subject_prefix', '[Service Desk #:reference]')
        );

        return (new MailMessage)
            ->subject($subject.' '.__("service-desk::service-desk.notifications.{$key}.subject", ['title' => $ticket->title]))
            ->greeting(__("service-desk::service-desk.notifications.{$key}.greeting"))
            ->line(__("service-desk::service-desk.notifications.{$key}.body", [
                'requester' => $ticket->user_name ?? $ticket->user_email ?? '-',
            ]))
            ->line(__("service-desk::service-desk.notifications.{$key}.title", ['title' => $ticket->title]))
            ->line(__("service-desk::service-desk.notifications.{$key}.priority", [
                'priority' => $ticket->priority->label(),
            ]))
            ->action(__('service-desk::service-desk.notifications.view_ticket'), $ticket->notificationUrl($notifiable))
            ->withSymfonyMessage(function ($message) use ($ticket) {
                $domain = parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost';
                $suffix = $this->comment === null ? 'operator' : 'operator-comment-'.$this->comment->id;
                $references = "<{$ticket->uuid}-created-{$ticket->id}@{$domain}>";

                $headers = $message->getHeaders();
                $headers->addTextHeader('X-ServiceDesk-Ticket-Ref', $ticket->reference_number);
                $headers->addIdHeader('Message-ID', "{$ticket->uuid}-{$suffix}-{$ticket->id}@{$domain}");
                $headers->addTextHeader('In-Reply-To', $references);
                $headers->addTextHeader('References', $references);
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
            'comment_id' => $this->comment?->id,
            'type' => $this->comment === null ? 'operator_new_ticket' : 'operator_requester_reply',
        ];
    }
}
