<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Events\SlaBreached;
use JeffersonGoncalves\ServiceDesk\Events\SlaNearBreach;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;

function makeSlaPolicyForBreach(): SlaPolicy
{
    return SlaPolicy::create([
        'name' => 'Standard SLA',
        'is_active' => true,
        'sort_order' => 0,
    ]);
}

it('marks a breached ticket SLA and dispatches SlaBreached', function () {
    Event::fake([SlaBreached::class, SlaNearBreach::class]);

    $policy = makeSlaPolicyForBreach();
    $ticket = Ticket::factory()->priority(TicketPriority::Medium)->create();

    TicketSla::create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subHour(),
        'first_response_breached' => false,
    ]);

    $this->artisan('service-desk:check-sla')->assertExitCode(0);

    expect(TicketSla::where('ticket_id', $ticket->id)->first()->first_response_breached)->toBeTrue();

    Event::assertDispatched(SlaBreached::class);
});

it('dispatches SlaNearBreach for tickets approaching their due date', function () {
    Event::fake([SlaBreached::class, SlaNearBreach::class]);

    $policy = makeSlaPolicyForBreach();
    $ticket = Ticket::factory()->priority(TicketPriority::Medium)->create();

    TicketSla::create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->addMinutes(10),
        'first_response_breached' => false,
    ]);

    $this->artisan('service-desk:check-sla')->assertExitCode(0);

    Event::assertDispatched(SlaNearBreach::class);
});
