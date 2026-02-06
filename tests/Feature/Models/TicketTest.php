<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Category;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Tag;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketWatcher;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::create(['name' => 'IT Support', 'slug' => 'it-support']);
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
});

// ── CRUD ────────────────────────────────────────────────────────────────────

it('can create a ticket with minimal data', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'My first ticket',
        'description' => 'I need help',
    ]);

    expect($ticket)->toBeInstanceOf(Ticket::class)
        ->and($ticket->title)->toBe('My first ticket')
        ->and($ticket->description)->toBe('I need help')
        ->and($ticket->department_id)->toBe($this->department->id);
});

it('can update a ticket', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Original title',
        'description' => 'Original description',
    ]);

    $ticket->update(['title' => 'Updated title']);

    expect($ticket->fresh()->title)->toBe('Updated title');
});

it('can soft delete a ticket', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Delete me',
        'description' => 'To be deleted',
    ]);

    $ticket->delete();

    expect(Ticket::find($ticket->id))->toBeNull()
        ->and(Ticket::withTrashed()->find($ticket->id))->not->toBeNull();
});

// ── Auto-generation (booted) ────────────────────────────────────────────────

it('auto generates uuid on creation', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'UUID test',
        'description' => 'Should have uuid',
    ]);

    expect($ticket->uuid)->not->toBeNull()
        ->and($ticket->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('auto generates reference number on creation', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Reference test',
        'description' => 'Should have reference',
    ]);

    expect($ticket->reference_number)->not->toBeNull()
        ->and($ticket->reference_number)->toStartWith('SD-');
});

it('generates sequential reference numbers', function () {
    $ticket1 = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Ticket 1',
        'description' => 'First ticket',
    ]);

    $ticket2 = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Ticket 2',
        'description' => 'Second ticket',
    ]);

    expect($ticket1->reference_number)->toBe('SD-00001')
        ->and($ticket2->reference_number)->toBe('SD-00002');
});

it('does not overwrite provided uuid', function () {
    $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    $ticket = Ticket::create([
        'uuid' => $uuid,
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Custom UUID',
        'description' => 'Has custom uuid',
    ]);

    expect($ticket->uuid)->toBe($uuid);
});

it('sets default status to open', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Status test',
        'description' => 'Default status',
    ]);

    expect($ticket->status)->toBe(TicketStatus::Open);
});

it('sets default priority to medium', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Priority test',
        'description' => 'Default priority',
    ]);

    expect($ticket->priority)->toBe(TicketPriority::Medium);
});

// ── Casts ───────────────────────────────────────────────────────────────────

it('casts status to TicketStatus enum', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Cast test',
        'description' => 'Status cast',
        'status' => 'in_progress',
    ]);

    expect($ticket->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->status)->toBeInstanceOf(TicketStatus::class);
});

it('casts priority to TicketPriority enum', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Cast test',
        'description' => 'Priority cast',
        'priority' => 'high',
    ]);

    expect($ticket->priority)->toBe(TicketPriority::High)
        ->and($ticket->priority)->toBeInstanceOf(TicketPriority::class);
});

it('casts metadata to array', function () {
    $meta = ['key' => 'value', 'nested' => ['a' => 1]];

    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Metadata test',
        'description' => 'Has metadata',
        'metadata' => $meta,
    ]);

    expect($ticket->fresh()->metadata)->toBe($meta);
});

// ── Relationships ───────────────────────────────────────────────────────────

it('belongs to a department', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Relation test',
        'description' => 'Department relation',
    ]);

    expect($ticket->department)->toBeInstanceOf(Department::class)
        ->and($ticket->department->id)->toBe($this->department->id);
});

it('belongs to a category', function () {
    $category = Category::create([
        'department_id' => $this->department->id,
        'name' => 'Hardware',
        'slug' => 'hardware',
    ]);

    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'category_id' => $category->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Category test',
        'description' => 'Has category',
    ]);

    expect($ticket->category)->toBeInstanceOf(Category::class)
        ->and($ticket->category->id)->toBe($category->id);
});

it('morphs to a user', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'User test',
        'description' => 'User relation',
    ]);

    expect($ticket->user)->toBeInstanceOf(User::class)
        ->and($ticket->user->id)->toBe($this->user->id);
});

it('morphs to an assigned operator', function () {
    $operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);

    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'assigned_to_type' => $operator->getMorphClass(),
        'assigned_to_id' => $operator->id,
        'title' => 'Assign test',
        'description' => 'Assigned ticket',
    ]);

    expect($ticket->assignedTo)->toBeInstanceOf(User::class)
        ->and($ticket->assignedTo->id)->toBe($operator->id);
});

it('has many watchers', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Watcher test',
        'description' => 'Has watchers',
    ]);

    $ticket->watchers()->create([
        'watcher_type' => $this->user->getMorphClass(),
        'watcher_id' => $this->user->id,
    ]);

    expect($ticket->watchers)->toHaveCount(1)
        ->and($ticket->watchers->first())->toBeInstanceOf(TicketWatcher::class);
});

it('has morph-to-many tags', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Tag test',
        'description' => 'Has tags',
    ]);

    $tag = Tag::create(['name' => 'Bug', 'slug' => 'bug']);
    $ticket->tags()->attach($tag->id);

    expect($ticket->tags)->toHaveCount(1)
        ->and($ticket->tags->first()->name)->toBe('Bug');
});

// ── Scopes ──────────────────────────────────────────────────────────────────

it('scopes by status', function () {
    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Open ticket',
        'description' => 'Open',
        'status' => TicketStatus::Open,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Closed ticket',
        'description' => 'Closed',
        'status' => TicketStatus::Closed,
    ]);

    expect(Ticket::byStatus(TicketStatus::Open)->count())->toBe(1)
        ->and(Ticket::byStatus(TicketStatus::Closed)->count())->toBe(1);
});

it('scopes by priority', function () {
    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Urgent ticket',
        'description' => 'Urgent',
        'priority' => TicketPriority::Urgent,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Low ticket',
        'description' => 'Low',
        'priority' => TicketPriority::Low,
    ]);

    expect(Ticket::byPriority(TicketPriority::Urgent)->count())->toBe(1)
        ->and(Ticket::byPriority(TicketPriority::Low)->count())->toBe(1);
});

it('scopes open tickets (excludes closed and resolved)', function () {
    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Open ticket',
        'description' => 'Open',
        'status' => TicketStatus::Open,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'In progress ticket',
        'description' => 'In progress',
        'status' => TicketStatus::InProgress,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Closed ticket',
        'description' => 'Closed',
        'status' => TicketStatus::Closed,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Resolved ticket',
        'description' => 'Resolved',
        'status' => TicketStatus::Resolved,
    ]);

    expect(Ticket::open()->count())->toBe(2);
});

it('scopes closed tickets (closed and resolved)', function () {
    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Open ticket',
        'description' => 'Open',
        'status' => TicketStatus::Open,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Closed ticket',
        'description' => 'Closed',
        'status' => TicketStatus::Closed,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Resolved ticket',
        'description' => 'Resolved',
        'status' => TicketStatus::Resolved,
    ]);

    expect(Ticket::closed()->count())->toBe(2);
});

it('scopes unassigned tickets', function () {
    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Unassigned',
        'description' => 'No operator',
    ]);

    $operator = User::create(['name' => 'Operator', 'email' => 'op@example.com']);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'assigned_to_type' => $operator->getMorphClass(),
        'assigned_to_id' => $operator->id,
        'title' => 'Assigned',
        'description' => 'Has operator',
    ]);

    expect(Ticket::unassigned()->count())->toBe(1);
});

it('scopes overdue tickets', function () {
    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Overdue ticket',
        'description' => 'Past due',
        'due_at' => now()->subDay(),
        'status' => TicketStatus::Open,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Not overdue',
        'description' => 'Future due',
        'due_at' => now()->addDay(),
        'status' => TicketStatus::Open,
    ]);

    Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Closed overdue',
        'description' => 'Past due but closed',
        'due_at' => now()->subDay(),
        'status' => TicketStatus::Closed,
    ]);

    expect(Ticket::overdue()->count())->toBe(1);
});

// ── Helper methods ──────────────────────────────────────────────────────────

it('identifies open tickets correctly', function () {
    $openTicket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Open',
        'description' => 'Open ticket',
        'status' => TicketStatus::Open,
    ]);

    $closedTicket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Closed',
        'description' => 'Closed ticket',
        'status' => TicketStatus::Closed,
    ]);

    expect($openTicket->isOpen())->toBeTrue()
        ->and($closedTicket->isOpen())->toBeFalse();
});

it('identifies closed tickets correctly', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Closed',
        'description' => 'Closed ticket',
        'status' => TicketStatus::Closed,
    ]);

    expect($ticket->isClosed())->toBeTrue();
});

it('identifies resolved tickets correctly', function () {
    $ticket = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Resolved',
        'description' => 'Resolved ticket',
        'status' => TicketStatus::Resolved,
    ]);

    expect($ticket->isResolved())->toBeTrue();
});

it('identifies assigned tickets correctly', function () {
    $operator = User::create(['name' => 'Operator', 'email' => 'op@example.com']);

    $assigned = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'assigned_to_type' => $operator->getMorphClass(),
        'assigned_to_id' => $operator->id,
        'title' => 'Assigned',
        'description' => 'Assigned ticket',
    ]);

    $unassigned = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Unassigned',
        'description' => 'Unassigned ticket',
    ]);

    expect($assigned->isAssigned())->toBeTrue()
        ->and($unassigned->isAssigned())->toBeFalse();
});

it('identifies overdue tickets correctly', function () {
    $overdue = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Overdue',
        'description' => 'Past due',
        'due_at' => now()->subDay(),
        'status' => TicketStatus::Open,
    ]);

    $notOverdue = Ticket::create([
        'department_id' => $this->department->id,
        'user_type' => $this->user->getMorphClass(),
        'user_id' => $this->user->id,
        'title' => 'Not overdue',
        'description' => 'Future due',
        'due_at' => now()->addDay(),
        'status' => TicketStatus::Open,
    ]);

    expect($overdue->isOverdue())->toBeTrue()
        ->and($notOverdue->isOverdue())->toBeFalse();
});

it('uses uuid as route key name', function () {
    $ticket = new Ticket;

    expect($ticket->getRouteKeyName())->toBe('uuid');
});
