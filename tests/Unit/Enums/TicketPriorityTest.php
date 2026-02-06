<?php

use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;

it('has all expected priorities', function () {
    expect(TicketPriority::cases())->toHaveCount(4);
});

it('has the correct case values', function () {
    expect(TicketPriority::Low->value)->toBe('low')
        ->and(TicketPriority::Medium->value)->toBe('medium')
        ->and(TicketPriority::High->value)->toBe('high')
        ->and(TicketPriority::Urgent->value)->toBe('urgent');
});

it('can be created from string values', function (string $value) {
    expect(TicketPriority::from($value))->toBeInstanceOf(TicketPriority::class);
})->with([
    'low',
    'medium',
    'high',
    'urgent',
]);

it('throws ValueError for invalid priority', function () {
    TicketPriority::from('critical');
})->throws(ValueError::class);

it('returns a label string', function () {
    expect(TicketPriority::Low->label())->toBeString()
        ->and(TicketPriority::Urgent->label())->toBeString();
});

it('returns correct numeric values', function () {
    expect(TicketPriority::Low->numericValue())->toBe(1)
        ->and(TicketPriority::Medium->numericValue())->toBe(2)
        ->and(TicketPriority::High->numericValue())->toBe(3)
        ->and(TicketPriority::Urgent->numericValue())->toBe(4);
});

it('has numeric values in ascending order of severity', function () {
    expect(TicketPriority::Low->numericValue())
        ->toBeLessThan(TicketPriority::Medium->numericValue())
        ->and(TicketPriority::Medium->numericValue())
        ->toBeLessThan(TicketPriority::High->numericValue())
        ->and(TicketPriority::High->numericValue())
        ->toBeLessThan(TicketPriority::Urgent->numericValue());
});
