<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Events\SlaApplied;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\SlaTarget;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;

function makeSlaPolicyWithTargetForRecalc(): SlaPolicy
{
    $policy = SlaPolicy::create(['name' => 'Standard SLA', 'is_active' => true, 'sort_order' => 0]);

    SlaTarget::create([
        'sla_policy_id' => $policy->id,
        'priority' => TicketPriority::Medium->value,
        'first_response_time' => 60,
        'resolution_time' => 240,
    ]);

    return $policy;
}

it('recalculates SLA due dates for tickets with an existing ticket SLA', function () {
    Event::fake([SlaApplied::class]);

    $policy = makeSlaPolicyWithTargetForRecalc();
    $ticket = Ticket::factory()->priority(TicketPriority::Medium)->create();

    TicketSla::create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => TicketPriority::Medium->value,
    ]);

    $this->artisan('service-desk:recalculate-sla')->assertExitCode(0);

    Event::assertDispatched(SlaApplied::class);
});

it('filters by the given --policy option', function () {
    Event::fake([SlaApplied::class]);

    $policy = makeSlaPolicyWithTargetForRecalc();
    $ticket = Ticket::factory()->priority(TicketPriority::Medium)->create();

    TicketSla::create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => TicketPriority::Medium->value,
    ]);

    $this->artisan('service-desk:recalculate-sla', ['--policy' => $policy->id])->assertExitCode(0);

    Event::assertDispatched(SlaApplied::class);
});

it('fails when the given policy id does not exist', function () {
    $this->artisan('service-desk:recalculate-sla', ['--policy' => 999999])
        ->expectsOutputToContain('SLA Policy with ID 999999 not found.')
        ->assertExitCode(1);
});

it('reports when there are no tickets to recalculate', function () {
    $this->artisan('service-desk:recalculate-sla')
        ->expectsOutputToContain('No tickets found to recalculate.')
        ->assertExitCode(0);
});
