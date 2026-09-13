<?php

use JeffersonGoncalves\ServiceDesk\Enums\CommentType;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->ticket = Ticket::factory()->create();
    $this->author = User::create(['name' => 'Author', 'email' => 'author@example.com']);
});

// ── scopes ───────────────────────────────────────────────────────────────────

it('scopes public and internal comments', function () {
    TicketComment::factory()->for($this->ticket)->create(['is_internal' => false]);
    TicketComment::factory()->for($this->ticket)->note()->create();

    expect(TicketComment::public()->count())->toBe(1)
        ->and(TicketComment::internal()->count())->toBe(1);
});

it('scopes replies and notes', function () {
    TicketComment::factory()->for($this->ticket)->create(['type' => CommentType::Reply]);
    TicketComment::factory()->for($this->ticket)->note()->create();
    TicketComment::factory()->for($this->ticket)->system()->create();

    expect(TicketComment::replies()->count())->toBe(1)
        ->and(TicketComment::notes()->count())->toBe(1);
});

// ── helper methods ───────────────────────────────────────────────────────────

it('identifies reply comments', function () {
    $comment = TicketComment::factory()->for($this->ticket)->create(['type' => CommentType::Reply]);

    expect($comment->isReply())->toBeTrue()
        ->and($comment->isNote())->toBeFalse()
        ->and($comment->isSystem())->toBeFalse();
});

it('identifies note comments', function () {
    $comment = TicketComment::factory()->for($this->ticket)->note()->create();

    expect($comment->isNote())->toBeTrue()
        ->and($comment->isReply())->toBeFalse();
});

it('identifies system comments', function () {
    $comment = TicketComment::factory()->for($this->ticket)->system()->create();

    expect($comment->isSystem())->toBeTrue();
});

it('identifies internal comments', function () {
    $internal = TicketComment::factory()->for($this->ticket)->note()->create();
    $public = TicketComment::factory()->for($this->ticket)->create(['is_internal' => false]);

    expect($internal->isInternal())->toBeTrue()
        ->and($public->isInternal())->toBeFalse();
});
