<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\ServiceDesk\Enums\CommentType;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Services\CommentService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(CommentService::class);
    $this->ticket = Ticket::factory()->create();
    $this->author = User::create(['name' => 'Author', 'email' => 'author@example.com']);
});

// ── addReply() ───────────────────────────────────────────────────────────────

it('adds a public reply', function () {
    Event::fake([CommentAdded::class]);

    $comment = $this->service->addReply($this->ticket, $this->author, 'Thanks for reaching out!');

    expect($comment)->toBeInstanceOf(TicketComment::class)
        ->and($comment->body)->toBe('Thanks for reaching out!')
        ->and($comment->type)->toBe(CommentType::Reply)
        ->and($comment->is_internal)->toBeFalse()
        ->and($comment->author_id)->toBe($this->author->id);

    Event::assertDispatched(CommentAdded::class);
});

it('updates last_replied_at when a comment is added', function () {
    Event::fake([CommentAdded::class]);

    expect($this->ticket->last_replied_at)->toBeNull();

    $this->service->addReply($this->ticket, $this->author, 'A reply');

    expect($this->ticket->fresh()->last_replied_at)->not->toBeNull();
});

// ── addNote() ────────────────────────────────────────────────────────────────

it('adds an internal note', function () {
    Event::fake([CommentAdded::class]);

    $comment = $this->service->addNote($this->ticket, $this->author, 'Internal note for the team');

    expect($comment->type)->toBe(CommentType::Note)
        ->and($comment->is_internal)->toBeTrue();
});

// ── addSystemComment() ───────────────────────────────────────────────────────

it('adds a system comment', function () {
    $comment = $this->service->addSystemComment($this->ticket, 'Status changed automatically');

    expect($comment->type)->toBe(CommentType::System)
        ->and($comment->body)->toBe('Status changed automatically');
});

// ── attachments ──────────────────────────────────────────────────────────────

it('stores attachments passed with a comment', function () {
    Storage::fake('local');
    Event::fake([CommentAdded::class]);

    $comment = $this->service->addReply($this->ticket, $this->author, 'See attached', [
        'attachments' => [
            UploadedFile::fake()->create('doc.pdf', 50),
        ],
    ]);

    expect($comment->attachments)->toHaveCount(1)
        ->and($comment->attachments->first()->file_name)->toBe('doc.pdf');
});

// ── delete() ─────────────────────────────────────────────────────────────────

it('deletes a comment', function () {
    Event::fake([CommentAdded::class]);

    $comment = $this->service->addReply($this->ticket, $this->author, 'To be deleted');

    expect($this->service->delete($comment))->toBeTrue()
        ->and(TicketComment::find($comment->id))->toBeNull();
});
