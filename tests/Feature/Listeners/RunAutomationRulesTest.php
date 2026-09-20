<?php

use JeffersonGoncalves\ServiceDesk\Enums\AutomationAction;
use JeffersonGoncalves\ServiceDesk\Enums\AutomationTrigger;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Listeners\RunAutomationRules;
use JeffersonGoncalves\ServiceDesk\Models\AutomationRule;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;
use JeffersonGoncalves\ServiceDesk\Services\AutomationService;

beforeEach(function () {
    $this->listener = new RunAutomationRules(app(AutomationService::class));
    $this->department = Department::factory()->create();
    $this->ticket = Ticket::factory()->create(['department_id' => $this->department->id]);
});

it('evaluates TicketCreated-triggered rules on ticket creation', function () {
    AutomationRule::create([
        'name' => 'On create',
        'trigger_event' => AutomationTrigger::TicketCreated,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'urgent'],
        'is_active' => true,
    ]);

    $this->listener->handleTicketCreated(new TicketCreated($this->ticket));

    expect($this->ticket->fresh()->priority)->toBe(TicketPriority::Urgent);
});

it('evaluates TicketStatusChanged-triggered rules on status change', function () {
    AutomationRule::create([
        'name' => 'On status change',
        'trigger_event' => AutomationTrigger::TicketStatusChanged,
        'conditions' => [],
        'action' => AutomationAction::ChangeStatus,
        'action_config' => ['status' => 'resolved'],
        'is_active' => true,
    ]);

    $this->listener->handleTicketStatusChanged(new TicketStatusChanged($this->ticket, TicketStatus::Open, TicketStatus::InProgress));

    expect($this->ticket->fresh()->status)->toBe(TicketStatus::Resolved);
});

it('evaluates CommentAdded-triggered rules when a comment is added', function () {
    AutomationRule::create([
        'name' => 'On comment',
        'trigger_event' => AutomationTrigger::CommentAdded,
        'conditions' => [],
        'action' => AutomationAction::ChangePriority,
        'action_config' => ['priority' => 'low'],
        'is_active' => true,
    ]);

    $comment = TicketComment::factory()->for($this->ticket)->create();

    $this->listener->handleCommentAdded(new CommentAdded($this->ticket, $comment));

    expect($this->ticket->fresh()->priority)->toBe(TicketPriority::Low);
});

it('subscribes to ticket created, status changed, and comment added events', function () {
    $subscriptions = $this->listener->subscribe(app('events'));

    expect($subscriptions)->toHaveKeys([
        TicketCreated::class,
        TicketStatusChanged::class,
        CommentAdded::class,
    ]);
});
