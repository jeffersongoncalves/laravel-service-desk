<?php

use JeffersonGoncalves\ServiceDesk\Enums\AutomationAction;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationTrigger;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Models\AutomationRule;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

it('reports when there are no open tickets', function () {
    $this->artisan('service-desk:run-automations')
        ->expectsOutputToContain('No open tickets found.')
        ->assertExitCode(0);
});

it('applies matching rules to open tickets', function () {
    $ticket = Ticket::factory()->create();

    AutomationRule::create([
        'name' => 'Sweep rule',
        'trigger_event' => AutomationTrigger::TicketStatusChanged,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $this->artisan('service-desk:run-automations')
        ->expectsOutputToContain('1 ticket(s) matched at least one rule.')
        ->assertExitCode(0);

    expect($ticket->fresh()->priority)->toBe(TicketPriority::Urgent);
});

it('does not apply anything in dry-run mode', function () {
    $ticket = Ticket::factory()->create();

    AutomationRule::create([
        'name' => 'Sweep rule',
        'trigger_event' => AutomationTrigger::TicketStatusChanged,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $this->artisan('service-desk:run-automations', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN]')
        ->assertExitCode(0);

    expect($ticket->fresh()->priority)->toBe(TicketPriority::Medium);
});
