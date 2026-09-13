<?php

use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;

beforeEach(function () {
    $policy = SlaPolicy::create(['name' => 'Standard SLA', 'is_active' => true, 'sort_order' => 0]);
    $ticket = Ticket::factory()->priority(TicketPriority::Medium)->create();

    $this->ticketSla = TicketSla::create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => TicketPriority::Medium->value,
    ]);
});

// ── pause() / resume() ──────────────────────────────────────────────────────

it('pauses the SLA clock', function () {
    $this->ticketSla->pause();

    expect($this->ticketSla->fresh()->isPaused())->toBeTrue()
        ->and($this->ticketSla->fresh()->paused_at)->not->toBeNull();
});

it('does not reset paused_at when pausing an already paused SLA', function () {
    $this->ticketSla->pause();
    $firstPausedAt = $this->ticketSla->fresh()->paused_at;

    $this->ticketSla->pause();

    expect($this->ticketSla->fresh()->paused_at->equalTo($firstPausedAt))->toBeTrue();
});

it('resumes the SLA clock and accumulates paused minutes', function () {
    $this->ticketSla->update(['paused_at' => now()->subMinutes(30)]);

    $this->ticketSla->resume();

    $fresh = $this->ticketSla->fresh();

    expect($fresh->isPaused())->toBeFalse()
        ->and($fresh->paused_at)->toBeNull()
        ->and($fresh->paused_minutes)->toBeGreaterThanOrEqual(30);
});

it('does nothing when resuming an SLA that is not paused', function () {
    $this->ticketSla->resume();

    expect($this->ticketSla->fresh()->paused_minutes)->toBe(0);
});

// ── recordFirstResponse() / recordResolution() ──────────────────────────────

it('records the first response timestamp', function () {
    expect($this->ticketSla->first_responded_at)->toBeNull();

    $this->ticketSla->recordFirstResponse();

    expect($this->ticketSla->fresh()->first_responded_at)->not->toBeNull();
});

it('records the resolution timestamp', function () {
    expect($this->ticketSla->resolved_at)->toBeNull();

    $this->ticketSla->recordResolution();

    expect($this->ticketSla->fresh()->resolved_at)->not->toBeNull();
});

// ── isPaused() ───────────────────────────────────────────────────────────────

it('reports isPaused correctly', function () {
    expect($this->ticketSla->isPaused())->toBeFalse();

    $this->ticketSla->pause();

    expect($this->ticketSla->fresh()->isPaused())->toBeTrue();
});
