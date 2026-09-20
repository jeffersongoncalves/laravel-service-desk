<?php

use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;

it('has all expected statuses', function () {
    expect(TicketStatus::cases())->toHaveCount(6);
});

it('has the correct case values', function () {
    expect(TicketStatus::Open->value)->toBe('open')
        ->and(TicketStatus::Pending->value)->toBe('pending')
        ->and(TicketStatus::InProgress->value)->toBe('in_progress')
        ->and(TicketStatus::OnHold->value)->toBe('on_hold')
        ->and(TicketStatus::Resolved->value)->toBe('resolved')
        ->and(TicketStatus::Closed->value)->toBe('closed');
});

it('can be created from string values', function (string $value) {
    expect(TicketStatus::from($value))->toBeInstanceOf(TicketStatus::class);
})->with([
    'open',
    'pending',
    'in_progress',
    'on_hold',
    'resolved',
    'closed',
]);

it('throws ValueError for invalid status', function () {
    TicketStatus::from('invalid');
})->throws(ValueError::class);

it('returns a label string', function () {
    expect(TicketStatus::Open->label())->toBeString();
});

it('identifies statuses that pause SLA', function () {
    config()->set('service-desk.sla.pause_on_statuses', ['on_hold']);

    expect(TicketStatus::OnHold->pausesSla())->toBeTrue()
        ->and(TicketStatus::Open->pausesSla())->toBeFalse()
        ->and(TicketStatus::Pending->pausesSla())->toBeFalse()
        ->and(TicketStatus::InProgress->pausesSla())->toBeFalse()
        ->and(TicketStatus::Resolved->pausesSla())->toBeFalse()
        ->and(TicketStatus::Closed->pausesSla())->toBeFalse();
});

it('respects custom pause_on_statuses config', function () {
    config()->set('service-desk.sla.pause_on_statuses', ['on_hold', 'pending']);

    expect(TicketStatus::OnHold->pausesSla())->toBeTrue()
        ->and(TicketStatus::Pending->pausesSla())->toBeTrue()
        ->and(TicketStatus::Open->pausesSla())->toBeFalse();
});

it('defines allowed transitions for Open status', function () {
    $transitions = TicketStatus::Open->allowedTransitions();

    expect($transitions)->toContain(TicketStatus::Pending)
        ->toContain(TicketStatus::InProgress)
        ->toContain(TicketStatus::OnHold)
        ->toContain(TicketStatus::Resolved)
        ->toContain(TicketStatus::Closed);
});

it('defines allowed transitions for Resolved status', function () {
    $transitions = TicketStatus::Resolved->allowedTransitions();

    expect($transitions)->toContain(TicketStatus::Open)
        ->toContain(TicketStatus::Closed)
        ->not->toContain(TicketStatus::Pending)
        ->not->toContain(TicketStatus::InProgress);
});

it('allows Closed to reopen when config allows it', function () {
    config()->set('service-desk.ticket.allow_reopen', true);

    expect(TicketStatus::Closed->allowedTransitions())->toContain(TicketStatus::Open);
});

it('prevents Closed from reopening when config disallows it', function () {
    config()->set('service-desk.ticket.allow_reopen', false);

    expect(TicketStatus::Closed->allowedTransitions())->toBeEmpty();
});

it('checks canTransitionTo correctly', function () {
    expect(TicketStatus::Open->canTransitionTo(TicketStatus::InProgress))->toBeTrue()
        ->and(TicketStatus::Resolved->canTransitionTo(TicketStatus::Pending))->toBeFalse();
});

it('defines the canonical linear pipeline', function () {
    expect(TicketStatus::pipelineSteps())->toBe([
        TicketStatus::Open,
        TicketStatus::InProgress,
        TicketStatus::Resolved,
        TicketStatus::Closed,
    ]);
});

it('reports the correct pipeline step for statuses in the pipeline', function () {
    expect(TicketStatus::Open->pipelineStep())->toBe(1)
        ->and(TicketStatus::InProgress->pipelineStep())->toBe(2)
        ->and(TicketStatus::Resolved->pipelineStep())->toBe(3)
        ->and(TicketStatus::Closed->pipelineStep())->toBe(4);
});

it('reports the InProgress pipeline step for lateral waiting statuses', function () {
    expect(TicketStatus::Pending->pipelineStep())->toBe(TicketStatus::InProgress->pipelineStep())
        ->and(TicketStatus::OnHold->pipelineStep())->toBe(TicketStatus::InProgress->pipelineStep());
});
