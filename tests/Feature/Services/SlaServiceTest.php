<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Contracts\SlaCalculator;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Events\SlaApplied;
use JeffersonGoncalves\ServiceDesk\Events\SlaBreached;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\SlaTarget;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;
use JeffersonGoncalves\ServiceDesk\Services\BusinessHoursService;
use JeffersonGoncalves\ServiceDesk\Services\SlaService;

uses(RefreshDatabase::class);

beforeEach(function () {
    // SlaCalculator is bound in the service provider, so resolve via the container.
    $this->service = app(SlaService::class);
    $this->ticket = Ticket::factory()->priority(TicketPriority::Medium)->create();
});

// ── container bindings ────────────────────────────────────────────────────────

it('resolves SlaService from the container', function () {
    expect(app(SlaService::class))->toBeInstanceOf(SlaService::class);
});

it('binds SlaCalculator to the BusinessHoursService implementation', function () {
    expect(app(SlaCalculator::class))->toBeInstanceOf(BusinessHoursService::class);
});

function makePolicyWithTarget(array $targetOverrides = []): SlaPolicy
{
    $policy = SlaPolicy::create([
        'name' => 'Standard SLA',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    SlaTarget::create(array_merge([
        'sla_policy_id' => $policy->id,
        'priority' => TicketPriority::Medium->value,
        'first_response_time' => 60,
        'next_response_time' => 120,
        'resolution_time' => 240,
    ], $targetOverrides));

    return $policy->fresh();
}

// ── applyPolicy() ────────────────────────────────────────────────────────────

it('applies an SLA policy and computes due dates', function () {
    Event::fake([SlaApplied::class]);

    $policy = makePolicyWithTarget();

    $ticketSla = $this->service->applyPolicy($this->ticket, $policy);

    expect($ticketSla)->toBeInstanceOf(TicketSla::class)
        ->and($ticketSla->sla_policy_id)->toBe($policy->id)
        ->and($ticketSla->first_response_due_at)->not->toBeNull()
        ->and($ticketSla->resolution_due_at)->not->toBeNull()
        ->and($this->ticket->fresh()->sla_policy_id)->toBe($policy->id);

    // No business-hours schedule => plain minute offsets from now.
    expect($ticketSla->first_response_due_at->greaterThan(now()))->toBeTrue();

    Event::assertDispatched(SlaApplied::class);
});

it('returns null when SLA is disabled', function () {
    config()->set('service-desk.sla.enabled', false);

    $policy = makePolicyWithTarget();

    expect($this->service->applyPolicy($this->ticket, $policy))->toBeNull();
});

it('returns null when no matching target exists for the priority', function () {
    $policy = makePolicyWithTarget(['priority' => TicketPriority::Urgent->value]);

    expect($this->service->applyPolicy($this->ticket, $policy))->toBeNull();
});

it('finds a matching policy automatically', function () {
    $policy = makePolicyWithTarget();

    $ticketSla = $this->service->applyPolicy($this->ticket);

    expect($ticketSla)->not->toBeNull()
        ->and($ticketSla->sla_policy_id)->toBe($policy->id);
});

// ── checkBreaches() ──────────────────────────────────────────────────────────

it('marks first response breaches and dispatches an event', function () {
    Event::fake([SlaBreached::class]);

    $policy = makePolicyWithTarget();

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subHour(),
        'first_response_breached' => false,
    ]);

    $this->service->checkBreaches();

    $ticketSla = TicketSla::where('ticket_id', $this->ticket->id)->first();

    expect($ticketSla->first_response_breached)->toBeTrue();

    Event::assertDispatched(SlaBreached::class);
});

it('does not breach when already responded', function () {
    Event::fake([SlaBreached::class]);

    $policy = makePolicyWithTarget();

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subHour(),
        'first_responded_at' => now()->subMinutes(90),
        'first_response_breached' => false,
    ]);

    $this->service->checkBreaches();

    expect(TicketSla::where('ticket_id', $this->ticket->id)->first()->first_response_breached)->toBeFalse();

    Event::assertNotDispatched(SlaBreached::class);
});
