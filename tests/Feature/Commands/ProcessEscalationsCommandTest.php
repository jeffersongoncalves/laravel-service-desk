<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Enums\EscalationAction;
use JeffersonGoncalves\ServiceDesk\Enums\SlaBreachType;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Events\EscalationTriggered;
use JeffersonGoncalves\ServiceDesk\Models\EscalationRule;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;

it('triggers escalation rules for tickets past their due date', function () {
    Event::fake([EscalationTriggered::class]);

    $policy = SlaPolicy::create(['name' => 'Standard SLA', 'is_active' => true, 'sort_order' => 0]);

    EscalationRule::create([
        'sla_policy_id' => $policy->id,
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 10,
        'action' => EscalationAction::Notify,
        'action_config' => [],
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $ticket = Ticket::factory()->priority(TicketPriority::Medium)->create();

    TicketSla::create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subMinutes(20),
    ]);

    $this->artisan('service-desk:process-escalations')->assertExitCode(0);

    Event::assertDispatched(EscalationTriggered::class);
});

it('does nothing when there are no pending escalations', function () {
    Event::fake([EscalationTriggered::class]);

    $this->artisan('service-desk:process-escalations')->assertExitCode(0);

    Event::assertNotDispatched(EscalationTriggered::class);
});
