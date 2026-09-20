<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use JeffersonGoncalves\ServiceDesk\Enums\EscalationAction;
use JeffersonGoncalves\ServiceDesk\Enums\SlaBreachType;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Events\EscalationTriggered;
use JeffersonGoncalves\ServiceDesk\Models\EscalationRule;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketSla;
use JeffersonGoncalves\ServiceDesk\Notifications\EscalationNotification;
use JeffersonGoncalves\ServiceDesk\Services\EscalationService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->service = app(EscalationService::class);
    $this->ticket = Ticket::factory()->priority(TicketPriority::Medium)->create();
});

function makeEscalationRule(array $overrides = []): EscalationRule
{
    $policy = SlaPolicy::create([
        'name' => 'Standard SLA',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    return EscalationRule::create(array_merge([
        'sla_policy_id' => $policy->id,
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 30,
        'action' => EscalationAction::Notify,
        'action_config' => [],
        'is_active' => true,
        'sort_order' => 0,
    ], $overrides));
}

// ── handle() ─────────────────────────────────────────────────────────────────

it('dispatches EscalationTriggered when handling a rule', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule();

    $this->service->handle($rule, $this->ticket);

    Event::assertDispatched(EscalationTriggered::class, function ($event) use ($rule) {
        return $event->ticket->id === $this->ticket->id
            && $event->escalationRule->id === $rule->id;
    });
});

it('notifies the assigned operator on notify action', function () {
    Notification::fake();

    $operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);
    $this->ticket->update([
        'assigned_to_type' => $operator->getMorphClass(),
        'assigned_to_id' => $operator->id,
    ]);

    $rule = makeEscalationRule(['action' => EscalationAction::Notify]);

    $this->service->handle($rule, $this->ticket->fresh());

    Notification::assertSentTo($operator, EscalationNotification::class);
});

it('does not crash when the assigned operator class no longer exists', function () {
    Notification::fake();

    $operator = User::create(['name' => 'Operator', 'email' => 'operator@example.com']);
    $this->ticket->update([
        'assigned_to_type' => $operator->getMorphClass(),
        'assigned_to_id' => $operator->id,
    ]);
    $this->ticket->assigned_to_type = 'App\\Models\\LongGoneTenantOperator';
    $this->ticket->save();

    $rule = makeEscalationRule(['action' => EscalationAction::Notify]);

    $this->service->handle($rule, $this->ticket->fresh());

    Notification::assertNothingSent();
});

it('notifies explicit users configured on the rule', function () {
    Notification::fake();

    $user = User::create(['name' => 'Watcher', 'email' => 'watcher@example.com']);

    $rule = makeEscalationRule([
        'action' => EscalationAction::Notify,
        'action_config' => ['notify_users' => [$user->id]],
    ]);

    $this->service->handle($rule, $this->ticket);

    Notification::assertSentTo($user, EscalationNotification::class);
});

it('reassigns the ticket on reassign action', function () {
    Event::fake([EscalationTriggered::class]);

    $operator = User::create(['name' => 'New Owner', 'email' => 'new-owner@example.com']);

    $rule = makeEscalationRule([
        'action' => EscalationAction::Reassign,
        'action_config' => ['assign_to_id' => $operator->id],
    ]);

    $this->service->handle($rule, $this->ticket);

    expect($this->ticket->fresh()->assigned_to_id)->toBe($operator->id)
        ->and($this->ticket->fresh()->assigned_to_type)->toBe($operator->getMorphClass());
});

it('does not reassign when no assign_to_id is configured', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'action' => EscalationAction::Reassign,
        'action_config' => [],
    ]);

    $this->service->handle($rule, $this->ticket);

    expect($this->ticket->fresh()->assigned_to_id)->toBeNull();
});

it('changes the ticket priority on change_priority action', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'action' => EscalationAction::ChangePriority,
        'action_config' => ['priority' => TicketPriority::Urgent->value],
    ]);

    $this->service->handle($rule, $this->ticket);

    expect($this->ticket->fresh()->priority)->toBe(TicketPriority::Urgent);
});

it('does not change priority when none is configured', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'action' => EscalationAction::ChangePriority,
        'action_config' => [],
    ]);

    $this->service->handle($rule, $this->ticket);

    expect($this->ticket->fresh()->priority)->toBe(TicketPriority::Medium);
});

it('does nothing for custom action without a configured handler', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'action' => EscalationAction::Custom,
        'action_config' => [],
    ]);

    $this->service->handle($rule, $this->ticket);

    Event::assertDispatched(EscalationTriggered::class);
});

// ── processPendingEscalations() ─────────────────────────────────────────────

it('triggers an "after" rule once the due date has passed', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 10,
    ]);

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $rule->sla_policy_id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subMinutes(20),
    ]);

    $this->service->processPendingEscalations();

    Event::assertDispatched(EscalationTriggered::class);
});

it('does not trigger an "after" rule before the threshold is reached', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 30,
    ]);

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $rule->sla_policy_id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subMinutes(10),
    ]);

    $this->service->processPendingEscalations();

    Event::assertNotDispatched(EscalationTriggered::class);
});

it('triggers a "before" rule within the warning window', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'before',
        'minutes_before' => 30,
    ]);

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $rule->sla_policy_id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->addMinutes(10),
    ]);

    $this->service->processPendingEscalations();

    Event::assertDispatched(EscalationTriggered::class);
});

it('does not trigger when the ticket already responded', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 10,
    ]);

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $rule->sla_policy_id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subMinutes(20),
        'first_responded_at' => now()->subMinutes(15),
    ]);

    $this->service->processPendingEscalations();

    Event::assertNotDispatched(EscalationTriggered::class);
});

it('does not process escalations for closed or resolved tickets', function () {
    Event::fake([EscalationTriggered::class]);

    $this->ticket->update(['status' => 'closed']);

    $rule = makeEscalationRule([
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 10,
    ]);

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $rule->sla_policy_id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subMinutes(20),
    ]);

    $this->service->processPendingEscalations();

    Event::assertNotDispatched(EscalationTriggered::class);
});

it('does not process escalations while the SLA is paused', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 10,
    ]);

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $rule->sla_policy_id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subMinutes(20),
        'paused_at' => now(),
    ]);

    $this->service->processPendingEscalations();

    Event::assertNotDispatched(EscalationTriggered::class);
});

it('does not process an inactive escalation rule', function () {
    Event::fake([EscalationTriggered::class]);

    $rule = makeEscalationRule([
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 10,
        'is_active' => false,
    ]);

    TicketSla::create([
        'ticket_id' => $this->ticket->id,
        'sla_policy_id' => $rule->sla_policy_id,
        'priority_at_assignment' => TicketPriority::Medium->value,
        'first_response_due_at' => now()->subMinutes(20),
    ]);

    $this->service->processPendingEscalations();

    Event::assertNotDispatched(EscalationTriggered::class);
});
