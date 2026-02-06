<?php

use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;

it('has all expected service request statuses', function () {
    expect(ServiceRequestStatus::cases())->toHaveCount(6);
});

it('has the correct case values', function () {
    expect(ServiceRequestStatus::Pending->value)->toBe('pending')
        ->and(ServiceRequestStatus::Approved->value)->toBe('approved')
        ->and(ServiceRequestStatus::Rejected->value)->toBe('rejected')
        ->and(ServiceRequestStatus::InProgress->value)->toBe('in_progress')
        ->and(ServiceRequestStatus::Fulfilled->value)->toBe('fulfilled')
        ->and(ServiceRequestStatus::Cancelled->value)->toBe('cancelled');
});

it('can be created from string values', function (string $value) {
    expect(ServiceRequestStatus::from($value))->toBeInstanceOf(ServiceRequestStatus::class);
})->with([
    'pending',
    'approved',
    'rejected',
    'in_progress',
    'fulfilled',
    'cancelled',
]);

it('throws ValueError for invalid service request status', function () {
    ServiceRequestStatus::from('completed');
})->throws(ValueError::class);
