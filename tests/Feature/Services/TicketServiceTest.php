<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\TicketAssigned;
use JeffersonGoncalves\ServiceDesk\Events\TicketClosed;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketDeleted;
use JeffersonGoncalves\ServiceDesk\Events\TicketPriorityChanged;
use JeffersonGoncalves\ServiceDesk\Events\TicketReopened;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Events\TicketUpdated;
use JeffersonGoncalves\ServiceDesk\Exceptions\InvalidStatusTransitionException;
use JeffersonGoncalves\ServiceDesk\Exceptions\TicketNotFoundException;
use JeffersonGoncalves\ServiceDesk\Exceptions\UnauthorizedOperatorException;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;
use JeffersonGoncalves\ServiceDesk\Services\DepartmentService;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->service = app(TicketService::class);
    $this->department = Department::factory()->create();
    $this->user = User::create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $this->operator = User::create(['name' => 'Jane Operator', 'email' => 'jane@example.com']);

    app(DepartmentService::class)->addOperator($this->department, $this->operator);

    $this->packageEvents = [
        TicketCreated::class,
        TicketUpdated::class,
        TicketStatusChanged::class,
        TicketPriorityChanged::class,
        TicketClosed::class,
        TicketReopened::class,
        TicketAssigned::class,
        TicketDeleted::class,
    ];
});

// ── create() ────────────────────────────────────────────────────────────────

it('creates a ticket via service', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Computer not working',
        'description' => 'My computer wont turn on',
    ], $this->user);

    expect($ticket)->toBeInstanceOf(Ticket::class)
        ->and($ticket->title)->toBe('Computer not working')
        ->and($ticket->user_id)->toBe($this->user->id)
        ->and($ticket->user_type)->toBe($this->user->getMorphClass())
        ->and($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->priority)->toBe(TicketPriority::Medium)
        ->and($ticket->source)->toBe('web')
        ->and($ticket->uuid)->not->toBeNull()
        ->and($ticket->reference_number)->not->toBeNull();

    Event::assertDispatched(TicketCreated::class);
});

it('creates a ticket with explicit source', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Email ticket',
        'description' => 'From email',
        'source' => 'email',
    ], $this->user);

    expect($ticket->source)->toBe('email');
});

it('creates a ticket with custom priority', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Urgent issue',
        'description' => 'Very urgent',
        'priority' => TicketPriority::Urgent,
    ], $this->user);

    expect($ticket->priority)->toBe(TicketPriority::Urgent);
});

it('dispatches TicketCreated event on create', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Event test',
        'description' => 'Should fire event',
    ], $this->user);

    Event::assertDispatched(TicketCreated::class, function ($event) use ($ticket) {
        return $event->ticket->id === $ticket->id;
    });
});

// ── update() ────────────────────────────────────────────────────────────────

it('updates a ticket via service', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Original',
        'description' => 'Original description',
    ], $this->user);

    $updated = $this->service->update($ticket, ['title' => 'Updated Title']);

    expect($updated->title)->toBe('Updated Title');

    Event::assertDispatched(TicketUpdated::class);
});

it('dispatches TicketStatusChanged when status changes via update', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Status change',
        'description' => 'Will change status',
    ], $this->user);

    $this->service->update($ticket, ['status' => TicketStatus::InProgress], $this->operator);

    Event::assertDispatched(TicketStatusChanged::class);
});

it('dispatches TicketPriorityChanged when priority changes via update', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Priority change',
        'description' => 'Will change priority',
    ], $this->user);

    $this->service->update($ticket, ['priority' => TicketPriority::High], $this->operator);

    Event::assertDispatched(TicketPriorityChanged::class);
});

it('dispatches TicketClosed when status changes to closed via update', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Close test',
        'description' => 'Will be closed',
    ], $this->user);

    $this->service->update($ticket, ['status' => TicketStatus::Closed], $this->operator);

    Event::assertDispatched(TicketClosed::class);
});

it('dispatches TicketReopened when transitioning from closed to open', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Reopen test',
        'description' => 'Will be reopened',
        'status' => TicketStatus::Closed,
    ], $this->user);

    $this->service->update($ticket, ['status' => TicketStatus::Open], $this->operator);

    Event::assertDispatched(TicketReopened::class);
});

it('rejects an invalid status transition passed through the generic update() data array', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Skip transition',
        'description' => 'Will attempt an illegal jump',
    ], $this->user);

    // InProgress -> Open is not in InProgress's allowed transitions.
    $this->service->update($ticket, ['status' => TicketStatus::InProgress]);

    $this->service->update($ticket, ['status' => TicketStatus::Open, 'title' => 'Sneaky bulk edit']);
})->throws(InvalidStatusTransitionException::class);

it('accepts a raw string status value in the update() data array', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'String status',
        'description' => 'Status arrives as a raw string, e.g. from a form request',
    ], $this->user);

    $updated = $this->service->update($ticket, ['status' => 'in_progress']);

    expect($updated->status)->toBe(TicketStatus::InProgress);
});

it('pauses the SLA clock when status changes to a pausing status', function () {
    Event::fake($this->packageEvents);
    config()->set('service-desk.sla.pause_on_statuses', ['on_hold']);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'SLA pause test',
        'description' => 'Will be put on hold',
    ], $this->user);

    $policy = SlaPolicy::create(['name' => 'Standard SLA', 'is_active' => true, 'sort_order' => 0]);
    $ticketSla = TicketSla::create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => $ticket->priority->value,
    ]);

    expect($ticketSla->isPaused())->toBeFalse();

    $this->service->update($ticket, ['status' => TicketStatus::OnHold]);

    expect($ticketSla->fresh()->isPaused())->toBeTrue();
});

it('resumes the SLA clock when status changes away from a pausing status', function () {
    Event::fake($this->packageEvents);
    config()->set('service-desk.sla.pause_on_statuses', ['on_hold']);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'SLA resume test',
        'description' => 'Will resume from on hold',
        'status' => TicketStatus::OnHold,
    ], $this->user);

    $policy = SlaPolicy::create(['name' => 'Standard SLA', 'is_active' => true, 'sort_order' => 0]);
    $ticketSla = TicketSla::create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => $ticket->priority->value,
        'paused_at' => now()->subMinutes(10),
    ]);

    expect($ticketSla->isPaused())->toBeTrue();

    $this->service->update($ticket, ['status' => TicketStatus::InProgress]);

    expect($ticketSla->fresh()->isPaused())->toBeFalse()
        ->and($ticketSla->fresh()->paused_minutes)->toBeGreaterThanOrEqual(10);
});

it('does not crash when changing status on a ticket with no SLA record', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'No SLA test',
        'description' => 'No ticketSla row exists',
    ], $this->user);

    $result = $this->service->update($ticket, ['status' => TicketStatus::OnHold]);

    expect($result->status)->toBe(TicketStatus::OnHold);
});

// ── changeStatus() ──────────────────────────────────────────────────────────

it('changes ticket status with valid transition', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Transition test',
        'description' => 'Will transition',
    ], $this->user);

    $result = $this->service->changeStatus($ticket, TicketStatus::InProgress, $this->operator);

    expect($result->status)->toBe(TicketStatus::InProgress);
});

it('throws exception on invalid status transition', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Invalid transition',
        'description' => 'Should fail',
        'status' => TicketStatus::Closed,
    ], $this->user);

    // Closed can only go to Open, not InProgress
    $this->service->changeStatus($ticket, TicketStatus::InProgress);
})->throws(InvalidStatusTransitionException::class);

// ── operator-only status change enforcement ────────────────────────────────

it('allows a department operator to make any valid status change', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Operator authorized',
        'description' => 'Operator is registered for this department',
    ], $this->user);

    $result = $this->service->update($ticket, ['status' => TicketStatus::InProgress], $this->operator);

    expect($result->status)->toBe(TicketStatus::InProgress);
});

it('allows the requester to close their own ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Requester closes',
        'description' => 'Should be allowed',
    ], $this->user);

    $result = $this->service->update($ticket, ['status' => TicketStatus::Closed], $this->user);

    expect($result->status)->toBe(TicketStatus::Closed);
});

it('allows the requester to reopen their own closed ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Requester reopens',
        'description' => 'Should be allowed',
        'status' => TicketStatus::Closed,
    ], $this->user);

    $result = $this->service->update($ticket, ['status' => TicketStatus::Open], $this->user);

    expect($result->status)->toBe(TicketStatus::Open);
});

it('rejects the requester setting a non-close status themselves', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Requester forbidden',
        'description' => 'Requester cannot move to InProgress',
    ], $this->user);

    $this->service->update($ticket, ['status' => TicketStatus::InProgress], $this->user);
})->throws(UnauthorizedOperatorException::class);

it('rejects an unrelated third party changing status entirely', function () {
    Event::fake($this->packageEvents);

    $stranger = User::create(['name' => 'Stranger', 'email' => 'stranger@example.com']);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Stranger forbidden',
        'description' => 'Neither requester nor operator',
    ], $this->user);

    $this->service->update($ticket, ['status' => TicketStatus::Closed], $stranger);
})->throws(UnauthorizedOperatorException::class);

it('skips authorization entirely when no performer is given', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'System call',
        'description' => 'Internal callers without a performer are unaffected',
    ], $this->user);

    $result = $this->service->update($ticket, ['status' => TicketStatus::InProgress]);

    expect($result->status)->toBe(TicketStatus::InProgress);
});

// ── assign() ────────────────────────────────────────────────────────────────

it('assigns a ticket to an operator', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Assign test',
        'description' => 'Will be assigned',
    ], $this->user);

    $result = $this->service->assign($ticket, $this->operator, $this->user);

    expect($result->assigned_to_id)->toBe($this->operator->id)
        ->and($result->assigned_to_type)->toBe($this->operator->getMorphClass());

    Event::assertDispatched(TicketAssigned::class, function ($event) {
        return $event->ticket->assigned_to_id === $this->operator->id;
    });
});

// ── unassign() ──────────────────────────────────────────────────────────────

it('unassigns a ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Unassign test',
        'description' => 'Will be unassigned',
    ], $this->user);

    $this->service->assign($ticket, $this->operator);

    $result = $this->service->unassign($ticket);

    expect($result->assigned_to_id)->toBeNull()
        ->and($result->assigned_to_type)->toBeNull();
});

it('threads the performer through to the TicketUpdated event on unassign', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Unassign performer test',
        'description' => 'Performer should be carried on the event',
    ], $this->user);

    $this->service->assign($ticket, $this->operator);

    $this->service->unassign($ticket, $this->operator);

    Event::assertDispatched(TicketUpdated::class, function ($event) {
        return $event->performer?->is($this->operator);
    });
});

// ── close() ─────────────────────────────────────────────────────────────────

it('closes a ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Close test',
        'description' => 'Will be closed',
    ], $this->user);

    $result = $this->service->close($ticket, $this->operator);

    expect($result->status)->toBe(TicketStatus::Closed);
});

it('cannot close an already closed ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Already closed',
        'description' => 'Try closing again',
        'status' => TicketStatus::Closed,
    ], $this->user);

    $this->service->close($ticket);
})->throws(InvalidStatusTransitionException::class);

// ── reopen() ────────────────────────────────────────────────────────────────

it('reopens a closed ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Reopen test',
        'description' => 'Was closed, will reopen',
        'status' => TicketStatus::Closed,
    ], $this->user);

    $result = $this->service->reopen($ticket, $this->operator);

    expect($result->status)->toBe(TicketStatus::Open);
});

it('cannot reopen an already open ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Already open',
        'description' => 'Try reopening',
        'status' => TicketStatus::Open,
    ], $this->user);

    $this->service->reopen($ticket);
})->throws(InvalidStatusTransitionException::class);

// ── delete() ────────────────────────────────────────────────────────────────

it('deletes a ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Delete test',
        'description' => 'Will be deleted',
    ], $this->user);

    $result = $this->service->delete($ticket, $this->operator);

    expect($result)->toBeTrue()
        ->and(Ticket::find($ticket->id))->toBeNull();

    Event::assertDispatched(TicketDeleted::class);
});

// ── findByUuid() ────────────────────────────────────────────────────────────

it('finds a ticket by uuid', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'UUID find',
        'description' => 'Find by uuid',
    ], $this->user);

    $found = $this->service->findByUuid($ticket->uuid);

    expect($found->id)->toBe($ticket->id);
});

it('throws exception when uuid not found', function () {
    $this->service->findByUuid('non-existent-uuid');
})->throws(TicketNotFoundException::class);

// ── findByReference() ───────────────────────────────────────────────────────

it('finds a ticket by reference number', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Reference find',
        'description' => 'Find by reference',
    ], $this->user);

    $found = $this->service->findByReference($ticket->reference_number);

    expect($found->id)->toBe($ticket->id);
});

it('throws exception when reference not found', function () {
    $this->service->findByReference('SD-99999');
})->throws(TicketNotFoundException::class);

// ── addWatcher() / removeWatcher() ──────────────────────────────────────────

it('adds a watcher to a ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Watcher test',
        'description' => 'Add watcher',
    ], $this->user);

    $this->service->addWatcher($ticket, $this->operator);

    expect($ticket->watchers)->toHaveCount(1);
});

it('does not duplicate watchers', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Duplicate watcher',
        'description' => 'Add same watcher twice',
    ], $this->user);

    $this->service->addWatcher($ticket, $this->operator);
    $this->service->addWatcher($ticket, $this->operator);

    expect($ticket->watchers()->count())->toBe(1);
});

it('removes a watcher from a ticket', function () {
    Event::fake($this->packageEvents);

    $ticket = $this->service->create([
        'department_id' => $this->department->id,
        'title' => 'Remove watcher',
        'description' => 'Remove watcher test',
    ], $this->user);

    $this->service->addWatcher($ticket, $this->operator);
    $this->service->removeWatcher($ticket, $this->operator);

    expect($ticket->watchers()->count())->toBe(0);
});

// ── filter() ─────────────────────────────────────────────────────────────────

it('filters tickets by status', function () {
    Event::fake($this->packageEvents);

    $open = $this->service->create(['department_id' => $this->department->id, 'title' => 'Open one', 'description' => '...'], $this->user);
    $this->service->create(['department_id' => $this->department->id, 'title' => 'Closed one', 'description' => '...', 'status' => TicketStatus::Closed], $this->user);

    $results = $this->service->filter(['status' => 'open'])->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($open->id);
});

it('filters tickets by priority', function () {
    Event::fake($this->packageEvents);

    $urgent = $this->service->create(['department_id' => $this->department->id, 'title' => 'Urgent one', 'description' => '...', 'priority' => TicketPriority::Urgent], $this->user);
    $this->service->create(['department_id' => $this->department->id, 'title' => 'Low one', 'description' => '...', 'priority' => TicketPriority::Low], $this->user);

    $results = $this->service->filter(['priority' => 'urgent'])->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($urgent->id);
});

it('ignores an invalid status or priority filter value instead of erroring', function () {
    Event::fake($this->packageEvents);

    $this->service->create(['department_id' => $this->department->id, 'title' => 'Ticket', 'description' => '...'], $this->user);

    $results = $this->service->filter(['status' => 'not-a-real-status', 'priority' => 'not-a-real-priority'])->get();

    expect($results)->toHaveCount(1);
});

it('searches tickets by title, reference number, and description', function () {
    Event::fake($this->packageEvents);

    $match = $this->service->create(['department_id' => $this->department->id, 'title' => 'Printer is on fire', 'description' => 'Literally smoking'], $this->user);
    $this->service->create(['department_id' => $this->department->id, 'title' => 'Unrelated ticket', 'description' => 'Nothing to see here'], $this->user);

    $results = $this->service->filter(['search' => 'fire'])->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($match->id);

    $byReference = $this->service->filter(['search' => $match->reference_number])->get();

    expect($byReference)->toHaveCount(1)
        ->and($byReference->first()->id)->toBe($match->id);
});

it('escapes LIKE wildcards in the search term so they are not treated specially', function () {
    Event::fake($this->packageEvents);

    $this->service->create(['department_id' => $this->department->id, 'title' => 'Contains percent % literally', 'description' => '...'], $this->user);
    $this->service->create(['department_id' => $this->department->id, 'title' => 'Does not contain that symbol', 'description' => '...'], $this->user);

    $results = $this->service->filter(['search' => '%'])->get();

    expect($results)->toHaveCount(1);
});

it('escapes a literal exclamation mark, the internal escape character itself', function () {
    Event::fake($this->packageEvents);

    $this->service->create(['department_id' => $this->department->id, 'title' => 'Wow! Great!', 'description' => '...'], $this->user);
    $this->service->create(['department_id' => $this->department->id, 'title' => 'No punctuation here', 'description' => '...'], $this->user);

    $results = $this->service->filter(['search' => 'Wow!'])->get();

    expect($results)->toHaveCount(1);
});

it('sorts tickets by status in the enum declared sequence, not alphabetically', function () {
    Event::fake($this->packageEvents);

    $closed = $this->service->create(['department_id' => $this->department->id, 'title' => 'Closed', 'description' => '...', 'status' => TicketStatus::Closed], $this->user);
    $open = $this->service->create(['department_id' => $this->department->id, 'title' => 'Open', 'description' => '...'], $this->user);

    $results = $this->service->filter(['sort' => 'status', 'direction' => 'asc'])->get();

    expect($results->pluck('id')->all())->toBe([$open->id, $closed->id]);
});

it('sorts tickets by priority in the enum declared sequence, not alphabetically', function () {
    Event::fake($this->packageEvents);

    $low = $this->service->create(['department_id' => $this->department->id, 'title' => 'Low', 'description' => '...', 'priority' => TicketPriority::Low], $this->user);
    $urgent = $this->service->create(['department_id' => $this->department->id, 'title' => 'Urgent', 'description' => '...', 'priority' => TicketPriority::Urgent], $this->user);

    $results = $this->service->filter(['sort' => 'priority', 'direction' => 'desc'])->get();

    expect($results->pluck('id')->all())->toBe([$urgent->id, $low->id]);
});

it('falls back to created_at when an unknown sort column is given', function () {
    Event::fake($this->packageEvents);

    $this->service->create(['department_id' => $this->department->id, 'title' => 'Ticket', 'description' => '...'], $this->user);

    expect(fn () => $this->service->filter(['sort' => 'assigned_to_id; DROP TABLE users'])->get())
        ->not->toThrow(Throwable::class);
});
