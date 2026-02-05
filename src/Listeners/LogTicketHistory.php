<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Events\Dispatcher;
use JeffersonGoncalves\ServiceDesk\Enums\HistoryAction;
use JeffersonGoncalves\ServiceDesk\Events\AttachmentAdded;
use JeffersonGoncalves\ServiceDesk\Events\AttachmentRemoved;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Events\TicketAssigned;
use JeffersonGoncalves\ServiceDesk\Events\TicketClosed;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketPriorityChanged;
use JeffersonGoncalves\ServiceDesk\Events\TicketReopened;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Models\TicketHistory;

class LogTicketHistory
{
    public function handleTicketCreated(TicketCreated $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->ticket->user_type,
            'performer_id' => $event->ticket->user_id,
            'action' => HistoryAction::Created,
            'description' => __('service-desk::history.ticket_created'),
        ]);
    }

    public function handleTicketStatusChanged(TicketStatusChanged $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->performer?->getMorphClass(),
            'performer_id' => $event->performer?->getKey(),
            'action' => HistoryAction::StatusChanged,
            'field' => 'status',
            'old_value' => $event->oldStatus->value,
            'new_value' => $event->newStatus->value,
            'description' => __('service-desk::history.status_changed', [
                'old' => $event->oldStatus->label(),
                'new' => $event->newStatus->label(),
            ]),
        ]);
    }

    public function handleTicketPriorityChanged(TicketPriorityChanged $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->performer?->getMorphClass(),
            'performer_id' => $event->performer?->getKey(),
            'action' => HistoryAction::PriorityChanged,
            'field' => 'priority',
            'old_value' => $event->oldPriority->value,
            'new_value' => $event->newPriority->value,
            'description' => __('service-desk::history.priority_changed', [
                'old' => $event->oldPriority->label(),
                'new' => $event->newPriority->label(),
            ]),
        ]);
    }

    public function handleTicketAssigned(TicketAssigned $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->assignedBy?->getMorphClass(),
            'performer_id' => $event->assignedBy?->getKey(),
            'action' => HistoryAction::Assigned,
            'field' => 'assigned_to',
            'new_value' => $event->assignedTo->getKey(),
            'description' => __('service-desk::history.ticket_assigned', [
                'agent' => $event->assignedTo->name ?? $event->assignedTo->getKey(),
            ]),
            'metadata' => [
                'assigned_to_type' => $event->assignedTo->getMorphClass(),
                'assigned_to_id' => $event->assignedTo->getKey(),
            ],
        ]);
    }

    public function handleTicketClosed(TicketClosed $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->closedBy?->getMorphClass(),
            'performer_id' => $event->closedBy?->getKey(),
            'action' => HistoryAction::Closed,
            'description' => __('service-desk::history.ticket_closed'),
        ]);
    }

    public function handleTicketReopened(TicketReopened $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->reopenedBy?->getMorphClass(),
            'performer_id' => $event->reopenedBy?->getKey(),
            'action' => HistoryAction::Reopened,
            'description' => __('service-desk::history.ticket_reopened'),
        ]);
    }

    public function handleCommentAdded(CommentAdded $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->comment->author_type,
            'performer_id' => $event->comment->author_id,
            'action' => HistoryAction::CommentAdded,
            'description' => __('service-desk::history.comment_added', [
                'type' => $event->comment->type->value,
            ]),
            'metadata' => [
                'comment_id' => $event->comment->id,
                'comment_type' => $event->comment->type->value,
                'is_internal' => $event->comment->is_internal,
            ],
        ]);
    }

    public function handleAttachmentAdded(AttachmentAdded $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->attachment->uploaded_by_type,
            'performer_id' => $event->attachment->uploaded_by_id,
            'action' => HistoryAction::AttachmentAdded,
            'description' => __('service-desk::history.attachment_added', [
                'filename' => $event->attachment->file_name,
            ]),
            'metadata' => [
                'attachment_id' => $event->attachment->id,
                'file_name' => $event->attachment->file_name,
                'file_size' => $event->attachment->file_size,
                'mime_type' => $event->attachment->mime_type,
            ],
        ]);
    }

    public function handleAttachmentRemoved(AttachmentRemoved $event): void
    {
        TicketHistory::create([
            'ticket_id' => $event->ticket->id,
            'performer_type' => $event->removedBy?->getMorphClass(),
            'performer_id' => $event->removedBy?->getKey(),
            'action' => HistoryAction::AttachmentRemoved,
            'description' => __('service-desk::history.attachment_removed', [
                'filename' => $event->attachment->file_name,
            ]),
            'metadata' => [
                'attachment_id' => $event->attachment->id,
                'file_name' => $event->attachment->file_name,
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            TicketCreated::class => 'handleTicketCreated',
            TicketStatusChanged::class => 'handleTicketStatusChanged',
            TicketPriorityChanged::class => 'handleTicketPriorityChanged',
            TicketAssigned::class => 'handleTicketAssigned',
            TicketClosed::class => 'handleTicketClosed',
            TicketReopened::class => 'handleTicketReopened',
            CommentAdded::class => 'handleCommentAdded',
            AttachmentAdded::class => 'handleAttachmentAdded',
            AttachmentRemoved::class => 'handleAttachmentRemoved',
        ];
    }
}
