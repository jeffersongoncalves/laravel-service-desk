<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Notifications\NewCommentNotification;

class SendCommentAddedNotification implements ShouldQueue
{
    public function handle(CommentAdded $event): void
    {
        if (! config('service-desk.notifications.notify_on.comment_added', true)) {
            return;
        }

        $ticket = $event->ticket;
        $comment = $event->comment;

        // Skip internal comments — they should not trigger notifications to external users
        if ($comment->is_internal) {
            return;
        }

        $notification = new NewCommentNotification($ticket, $comment);

        // Notify the ticket owner if they are not the comment author
        $ticketOwner = $ticket->user;

        if ($ticketOwner && ! $this->isSameUser($ticketOwner, $comment)) {
            /** @phpstan-ignore method.notFound */
            $ticketOwner->notify($notification);
        }

        // Notify watchers if they are not the comment author
        foreach ($ticket->watchers as $watcher) {
            $watcherUser = $watcher->watcher;

            if (! $watcherUser) {
                continue;
            }

            // Skip if the watcher is the comment author
            if ($this->isSameUser($watcherUser, $comment)) {
                continue;
            }

            // Skip if the watcher is the ticket owner (already notified)
            if ($ticketOwner
                && $watcherUser->getMorphClass() === $ticketOwner->getMorphClass()
                && $watcherUser->getKey() === $ticketOwner->getKey()
            ) {
                continue;
            }

            /** @phpstan-ignore method.notFound */
            $watcherUser->notify($notification);
        }
    }

    protected function isSameUser(object $user, object $comment): bool
    {
        return $user->getMorphClass() === $comment->author_type
            && $user->getKey() == $comment->author_id;
    }
}
