<?php

use JeffersonGoncalves\ServiceDesk\Enums\EscalationAction;
use JeffersonGoncalves\ServiceDesk\Enums\SlaBreachType;
use JeffersonGoncalves\ServiceDesk\Models\EscalationRule;
use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Notifications\EscalationNotification;

beforeEach(function () {
    $this->ticket = Ticket::factory()->create(['title' => 'Cannot print']);

    $policy = SlaPolicy::create(['name' => 'Standard SLA', 'is_active' => true, 'sort_order' => 0]);

    $this->rule = EscalationRule::create([
        'sla_policy_id' => $policy->id,
        'breach_type' => SlaBreachType::FirstResponse,
        'trigger_type' => 'after',
        'minutes_before' => 30,
        'action' => EscalationAction::Notify,
        'is_active' => true,
        'sort_order' => 0,
    ]);
});

it('sends via the configured notification channels', function () {
    config()->set('service-desk.notifications.channels', ['mail']);

    $notification = new EscalationNotification($this->ticket, $this->rule);

    expect($notification->via((object) []))->toBe(['mail']);
});

it('builds a mail message referencing the ticket', function () {
    $notification = new EscalationNotification($this->ticket, $this->rule);

    $mail = $notification->toMail((object) []);

    expect($mail->subject)->toContain($this->ticket->reference_number);
});

it('builds an array payload with the escalation rule data', function () {
    $notification = new EscalationNotification($this->ticket, $this->rule);

    $array = $notification->toArray((object) []);

    expect($array)->toMatchArray([
        'ticket_id' => $this->ticket->id,
        'ticket_uuid' => $this->ticket->uuid,
        'reference_number' => $this->ticket->reference_number,
        'title' => 'Cannot print',
        'escalation_rule_id' => $this->rule->id,
        'breach_type' => 'first_response',
        'action' => 'notify',
        'type' => 'escalation',
    ]);
});
