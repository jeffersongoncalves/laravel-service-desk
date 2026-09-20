<?php

use JeffersonGoncalves\ServiceDesk\Enums\AutomationTrigger;

it('has all expected triggers', function () {
    expect(AutomationTrigger::cases())->toHaveCount(3);
});

it('has the correct case values', function () {
    expect(AutomationTrigger::TicketCreated->value)->toBe('ticket_created')
        ->and(AutomationTrigger::TicketStatusChanged->value)->toBe('ticket_status_changed')
        ->and(AutomationTrigger::CommentAdded->value)->toBe('comment_added');
});

it('can be created from string values', function (string $value) {
    expect(AutomationTrigger::from($value))->toBeInstanceOf(AutomationTrigger::class);
})->with([
    'ticket_created',
    'ticket_status_changed',
    'comment_added',
]);

it('throws ValueError for invalid trigger', function () {
    AutomationTrigger::from('invalid');
})->throws(ValueError::class);

it('returns a label string', function () {
    expect(AutomationTrigger::TicketCreated->label())->toBeString();
});
