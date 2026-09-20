<?php

use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->requester = User::create(['name' => 'Requester Doe', 'email' => 'requester@example.com']);
    $this->operator = User::create(['name' => 'Operator Doe', 'email' => 'operator@example.com']);
});

it('snapshots the requester name and email when a ticket is created', function () {
    $ticket = Ticket::factory()->create([
        'user_type' => $this->requester->getMorphClass(),
        'user_id' => $this->requester->id,
    ]);

    expect($ticket->user_name)->toBe('Requester Doe')
        ->and($ticket->user_email)->toBe('requester@example.com');
});

it('snapshots the assigned operator name and email when a ticket is assigned', function () {
    $ticket = Ticket::factory()->create();

    $ticket->update([
        'assigned_to_type' => $this->operator->getMorphClass(),
        'assigned_to_id' => $this->operator->id,
    ]);

    expect($ticket->assigned_to_name)->toBe('Operator Doe')
        ->and($ticket->assigned_to_email)->toBe('operator@example.com');
});

it('clears the assigned operator snapshot when unassigned', function () {
    $ticket = Ticket::factory()->assignedTo($this->operator)->create();

    $ticket->update(['assigned_to_type' => null, 'assigned_to_id' => null]);

    expect($ticket->assigned_to_name)->toBeNull()
        ->and($ticket->assigned_to_email)->toBeNull();
});

it('resolves the live requester when its class exists', function () {
    $ticket = Ticket::factory()->create([
        'user_type' => $this->requester->getMorphClass(),
        'user_id' => $this->requester->id,
    ]);

    expect($ticket->resolvedUser()?->is($this->requester))->toBeTrue();
});

it('returns null instead of crashing when the actor class no longer exists', function () {
    $ticket = Ticket::factory()->create([
        'user_type' => $this->requester->getMorphClass(),
        'user_id' => $this->requester->id,
    ]);

    $ticket->user_type = 'App\\Models\\LongGoneTenantUser';
    $ticket->save();

    expect(fn () => $ticket->resolvedUser())->not->toThrow(Throwable::class)
        ->and($ticket->resolvedUser())->toBeNull();
});

it('keeps the last known snapshot when reassigned to a class that no longer exists', function () {
    $ticket = Ticket::factory()->create([
        'user_type' => $this->requester->getMorphClass(),
        'user_id' => $this->requester->id,
    ]);

    $ticket->user_type = 'App\\Models\\LongGoneTenantUser';
    $ticket->save();

    expect($ticket->user_name)->toBe('Requester Doe')
        ->and($ticket->user_email)->toBe('requester@example.com');
});

it('snapshots the comment author name and email on creation', function () {
    $comment = TicketComment::factory()->create([
        'author_type' => $this->requester->getMorphClass(),
        'author_id' => $this->requester->id,
    ]);

    expect($comment->author_name)->toBe('Requester Doe')
        ->and($comment->author_email)->toBe('requester@example.com');
});

it('returns null from resolvedAuthor instead of crashing when the author class no longer exists', function () {
    $comment = TicketComment::factory()->create([
        'author_type' => $this->requester->getMorphClass(),
        'author_id' => $this->requester->id,
    ]);

    $comment->author_type = 'App\\Models\\LongGoneTenantUser';
    $comment->save();

    expect(fn () => $comment->resolvedAuthor())->not->toThrow(Throwable::class)
        ->and($comment->resolvedAuthor())->toBeNull();
});
