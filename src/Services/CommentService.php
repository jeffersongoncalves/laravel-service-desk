<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\ServiceDesk\Enums\CommentType;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;

class CommentService
{
    public function __construct(
        protected AttachmentService $attachmentService,
    ) {}

    public function addReply(Ticket $ticket, Model $author, string $body, array $options = []): TicketComment
    {
        return $this->addComment($ticket, $author, $body, CommentType::Reply, $options);
    }

    public function addNote(Ticket $ticket, Model $author, string $body, array $options = []): TicketComment
    {
        return $this->addComment($ticket, $author, $body, CommentType::Note, array_merge($options, ['is_internal' => true]));
    }

    public function addSystemComment(Ticket $ticket, string $body, array $options = []): TicketComment
    {
        return DB::transaction(function () use ($ticket, $body, $options) {
            /** @var TicketComment $comment */
            $comment = $ticket->comments()->create([
                'author_type' => config('service-desk.models.operator'),
                'author_id' => 0,
                'body' => $body,
                'type' => CommentType::System,
                'is_internal' => $options['is_internal'] ?? false,
                'email_message_id' => $options['email_message_id'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);

            return $comment;
        });
    }

    public function addComment(Ticket $ticket, Model $author, string $body, CommentType $type, array $options = []): TicketComment
    {
        return DB::transaction(function () use ($ticket, $author, $body, $type, $options) {
            /** @var TicketComment $comment */
            $comment = $ticket->comments()->create([
                'author_type' => $author->getMorphClass(),
                'author_id' => $author->getKey(),
                'body' => $body,
                'type' => $type,
                'is_internal' => $options['is_internal'] ?? false,
                'email_message_id' => $options['email_message_id'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);

            if (! empty($options['attachments'])) {
                foreach ($options['attachments'] as $file) {
                    $this->attachmentService->store($ticket, $file, $author, $comment);
                }
            }

            $ticket->update(['last_replied_at' => now()]);

            event(new CommentAdded($ticket, $comment));

            return $comment->load('attachments');
        });
    }

    public function delete(TicketComment $comment): bool
    {
        return $comment->delete();
    }
}
