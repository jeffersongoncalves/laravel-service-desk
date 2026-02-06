<?php

use JeffersonGoncalves\ServiceDesk\Enums\ArticleStatus;

it('has all expected article statuses', function () {
    expect(ArticleStatus::cases())->toHaveCount(3);
});

it('has the correct case values', function () {
    expect(ArticleStatus::Draft->value)->toBe('draft')
        ->and(ArticleStatus::Published->value)->toBe('published')
        ->and(ArticleStatus::Archived->value)->toBe('archived');
});

it('can be created from string values', function (string $value) {
    expect(ArticleStatus::from($value))->toBeInstanceOf(ArticleStatus::class);
})->with([
    'draft',
    'published',
    'archived',
]);

it('throws ValueError for invalid article status', function () {
    ArticleStatus::from('deleted');
})->throws(ValueError::class);
