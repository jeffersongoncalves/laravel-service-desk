<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\ServiceDesk\Enums\CommentType;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Category;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Tag;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;

uses(RefreshDatabase::class);

it('builds a department via factory', function () {
    $department = Department::factory()->create();

    expect($department)->toBeInstanceOf(Department::class)
        ->and($department->name)->not->toBeNull()
        ->and($department->slug)->not->toBeNull();
});

it('builds a category with a department via factory', function () {
    $category = Category::factory()->create();

    expect($category->department)->toBeInstanceOf(Department::class);
});

it('builds a tag via factory', function () {
    expect(Tag::factory()->create())->toBeInstanceOf(Tag::class);
});

it('builds a ticket with related department and user via factory', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket)->toBeInstanceOf(Ticket::class)
        ->and($ticket->department)->toBeInstanceOf(Department::class)
        ->and($ticket->user_id)->not->toBeNull()
        ->and($ticket->reference_number)->toStartWith('SD-');
});

it('applies ticket factory states', function () {
    $ticket = Ticket::factory()
        ->status(TicketStatus::Closed)
        ->priority(TicketPriority::Urgent)
        ->create();

    expect($ticket->status)->toBe(TicketStatus::Closed)
        ->and($ticket->priority)->toBe(TicketPriority::Urgent);
});

it('builds a ticket comment via factory', function () {
    $comment = TicketComment::factory()->note()->create();

    expect($comment)->toBeInstanceOf(TicketComment::class)
        ->and($comment->ticket)->toBeInstanceOf(Ticket::class)
        ->and($comment->type)->toBe(CommentType::Note)
        ->and($comment->is_internal)->toBeTrue();
});
