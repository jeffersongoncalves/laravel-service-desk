<?php

use Illuminate\Support\Facades\Notification;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Listeners\SendCommentAddedNotification;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Notifications\NewCommentNotification;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->listener = new SendCommentAddedNotification;
    $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.com']);
    $this->ticket = Ticket::factory()->create([
        'user_type' => $this->owner->getMorphClass(),
        'user_id' => $this->owner->id,
    ]);
});

it('notifies the ticket owner about a public comment from someone else', function () {
    Notification::fake();

    $author = User::create(['name' => 'Agent', 'email' => 'agent@example.com']);
    $comment = TicketComment::factory()->for($this->ticket)->create([
        'author_type' => $author->getMorphClass(),
        'author_id' => $author->id,
        'is_internal' => false,
    ]);

    $this->listener->handle(new CommentAdded($this->ticket, $comment));

    Notification::assertSentTo($this->owner, NewCommentNotification::class);
});

it('does not notify the comment author about their own comment', function () {
    Notification::fake();

    $comment = TicketComment::factory()->for($this->ticket)->create([
        'author_type' => $this->owner->getMorphClass(),
        'author_id' => $this->owner->id,
        'is_internal' => false,
    ]);

    $this->listener->handle(new CommentAdded($this->ticket, $comment));

    Notification::assertNothingSent();
});

it('does not notify anyone for internal comments', function () {
    Notification::fake();

    $author = User::create(['name' => 'Agent', 'email' => 'agent@example.com']);
    $comment = TicketComment::factory()->for($this->ticket)->note()->create([
        'author_type' => $author->getMorphClass(),
        'author_id' => $author->id,
    ]);

    $this->listener->handle(new CommentAdded($this->ticket, $comment));

    Notification::assertNothingSent();
});

it('notifies watchers other than the comment author and ticket owner', function () {
    Notification::fake();

    $author = User::create(['name' => 'Agent', 'email' => 'agent@example.com']);
    $watcher = User::create(['name' => 'Watcher', 'email' => 'watcher@example.com']);

    app(TicketService::class)->addWatcher($this->ticket, $watcher);

    $comment = TicketComment::factory()->for($this->ticket)->create([
        'author_type' => $author->getMorphClass(),
        'author_id' => $author->id,
        'is_internal' => false,
    ]);

    $this->listener->handle(new CommentAdded($this->ticket, $comment));

    Notification::assertSentTo($watcher, NewCommentNotification::class);
    Notification::assertSentTo($this->owner, NewCommentNotification::class);
});

it('does not send anything when comment notifications are disabled', function () {
    Notification::fake();
    config()->set('service-desk.notifications.notify_on.comment_added', false);

    $author = User::create(['name' => 'Agent', 'email' => 'agent@example.com']);
    $comment = TicketComment::factory()->for($this->ticket)->create([
        'author_type' => $author->getMorphClass(),
        'author_id' => $author->id,
        'is_internal' => false,
    ]);

    $this->listener->handle(new CommentAdded($this->ticket, $comment));

    Notification::assertNothingSent();
});
