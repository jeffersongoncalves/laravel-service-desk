<?php

use JeffersonGoncalves\ServiceDesk\Enums\AutomationAction;

it('has all expected actions', function () {
    expect(AutomationAction::cases())->toHaveCount(4);
});

it('has the correct case values', function () {
    expect(AutomationAction::Reassign->value)->toBe('reassign')
        ->and(AutomationAction::ChangePriority->value)->toBe('change_priority')
        ->and(AutomationAction::ChangeStatus->value)->toBe('change_status')
        ->and(AutomationAction::AddTag->value)->toBe('add_tag');
});

it('can be created from string values', function (string $value) {
    expect(AutomationAction::from($value))->toBeInstanceOf(AutomationAction::class);
})->with([
    'reassign',
    'change_priority',
    'change_status',
    'add_tag',
]);

it('throws ValueError for invalid action', function () {
    AutomationAction::from('invalid');
})->throws(ValueError::class);

it('returns a label string', function () {
    expect(AutomationAction::Reassign->label())->toBeString();
});
