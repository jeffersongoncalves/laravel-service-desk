<?php

use JeffersonGoncalves\ServiceDesk\Enums\AutomationAction;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationTrigger;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\AutomationRule;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Tag;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\AutomationService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->service = app(AutomationService::class);
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->user = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);
    $this->operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);
});

it('applies reassign when conditions match', function () {
    $ticket = Ticket::factory()->create([
        'department_id' => $this->department->id,
        'priority' => TicketPriority::High,
    ]);

    AutomationRule::create([
        'name' => 'Route high priority IT tickets',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [
            ['field' => 'department_id', 'operator' => 'equals', 'value' => $this->department->id],
            ['field' => 'priority', 'operator' => 'equals', 'value' => 'high'],
        ],
        'action' => AutomationAction::Reassign,
        'action_config' => [
            'assign_to_type' => $this->operator->getMorphClass(),
            'assign_to_id' => $this->operator->id,
        ],
        'is_active' => true,
    ]);

    $matched = $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($matched)->toHaveCount(1)
        ->and($ticket->fresh()->assigned_to_id)->toBe($this->operator->id);
});

it('does not apply when a condition does not match', function () {
    $ticket = Ticket::factory()->create([
        'department_id' => $this->otherDepartment->id,
        'priority' => TicketPriority::High,
    ]);

    AutomationRule::create([
        'name' => 'Route high priority IT tickets',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [
            ['field' => 'department_id', 'operator' => 'equals', 'value' => $this->department->id],
        ],
        'action' => AutomationAction::Reassign,
        'action_config' => [
            'assign_to_type' => $this->operator->getMorphClass(),
            'assign_to_id' => $this->operator->id,
        ],
        'is_active' => true,
    ]);

    $matched = $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($matched)->toBeEmpty()
        ->and($ticket->fresh()->assigned_to_id)->toBeNull();
});

it('ignores rules for a different trigger event', function () {
    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);

    AutomationRule::create([
        'name' => 'Status-only rule',
        'trigger_event' => AutomationTrigger::TicketStatusChanged,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $matched = $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($matched)->toBeEmpty();
});

it('ignores an inactive rule', function () {
    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);

    AutomationRule::create([
        'name' => 'Inactive rule',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => false,
    ]);

    $matched = $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($matched)->toBeEmpty();
});

it('supports the in operator against a list of values', function () {
    $ticket = Ticket::factory()->create([
        'department_id' => $this->department->id,
        'status' => TicketStatus::Pending,
    ]);

    AutomationRule::create([
        'name' => 'Waiting statuses',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [
            ['field' => 'status', 'operator' => 'in', 'value' => ['pending', 'on_hold']],
        ],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'low'],
        'is_active' => true,
    ]);

    $matched = $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($matched)->toHaveCount(1)
        ->and($ticket->fresh()->priority)->toBe(TicketPriority::Low);
});

it('rejects a condition field that is not in the allow-list', function () {
    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);

    AutomationRule::create([
        'name' => 'Malicious rule',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [
            ['field' => 'id', 'operator' => 'equals', 'value' => $ticket->id],
        ],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $matched = $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($matched)->toBeEmpty();
});

it('changes status via the change_status action', function () {
    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);

    AutomationRule::create([
        'name' => 'Auto in-progress',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [],
        'action' => AutomationAction::ChangeStatus,
        'action_config' => ['status' => 'in_progress'],
        'is_active' => true,
    ]);

    $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($ticket->fresh()->status)->toBe(TicketStatus::InProgress);
});

it('adds a tag via the add_tag action', function () {
    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);
    $tag = Tag::create(['name' => 'auto-tagged', 'slug' => 'auto-tagged']);

    AutomationRule::create([
        'name' => 'Auto tag',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [],
        'action' => AutomationAction::AddTag,
        'action_config' => ['tag_ids' => [$tag->id]],
        'is_active' => true,
    ]);

    $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($ticket->fresh()->tags()->pluck('name'))->toContain('auto-tagged');
});

it('does not reapply the same rule to the same ticket twice (anti-loop guard)', function () {
    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);

    $rule = AutomationRule::create([
        'name' => 'Repeatable-looking rule',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);
    $ticket->update(['priority' => TicketPriority::Low]);

    $secondRun = $this->service->evaluate($ticket->fresh(), AutomationTrigger::TicketCreated);

    expect($secondRun)->toBeEmpty()
        ->and($ticket->fresh()->metadata['automation_applied_rule_ids'])->toBe([$rule->id])
        ->and($ticket->fresh()->priority)->toBe(TicketPriority::Low);
});

it('reports matches without applying anything in dry-run mode', function () {
    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);

    AutomationRule::create([
        'name' => 'Dry run rule',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $matched = $this->service->evaluate($ticket, AutomationTrigger::TicketCreated, dryRun: true);

    expect($matched)->toHaveCount(1)
        ->and($ticket->fresh()->priority)->toBe(TicketPriority::Medium)
        ->and($ticket->fresh()->metadata)->toBeNull();
});

it('does nothing when the automation module is disabled', function () {
    config()->set('service-desk.automation.enabled', false);

    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);

    AutomationRule::create([
        'name' => 'Should not run',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $matched = $this->service->evaluate($ticket, AutomationTrigger::TicketCreated);

    expect($matched)->toBeEmpty();
});

it('evaluateAll ignores trigger_event and checks every active rule', function () {
    $ticket = Ticket::factory()->create(['department_id' => $this->department->id]);

    AutomationRule::create([
        'name' => 'Only fires on status change normally',
        'trigger_event' => AutomationTrigger::TicketStatusChanged,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $matched = $this->service->evaluateAll($ticket);

    expect($matched)->toHaveCount(1)
        ->and($ticket->fresh()->priority)->toBe(TicketPriority::Urgent);
});
