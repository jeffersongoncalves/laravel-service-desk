<?php

use JeffersonGoncalves\ServiceDesk\Enums\CommentType;

it('has all expected comment types', function () {
    expect(CommentType::cases())->toHaveCount(3);
});

it('has the correct case values', function () {
    expect(CommentType::Reply->value)->toBe('reply')
        ->and(CommentType::Note->value)->toBe('note')
        ->and(CommentType::System->value)->toBe('system');
});

it('can be created from string values', function (string $value) {
    expect(CommentType::from($value))->toBeInstanceOf(CommentType::class);
})->with([
    'reply',
    'note',
    'system',
]);

it('throws ValueError for invalid comment type', function () {
    CommentType::from('invalid');
})->throws(ValueError::class);
