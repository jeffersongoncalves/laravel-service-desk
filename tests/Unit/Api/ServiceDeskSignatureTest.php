<?php

use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSignature;

it('builds a canonical string from app key, method, uri, timestamp, nonce, and a body hash', function () {
    $canonical = ServiceDeskSignature::canonical('satellite-1', 'post', '/service-desk/api/tickets', '1700000000', 'abc123', '{"title":"Hi"}');

    expect($canonical)->toBe(implode("\n", [
        'satellite-1',
        'POST',
        '/service-desk/api/tickets',
        '1700000000',
        'abc123',
        hash('sha256', '{"title":"Hi"}'),
    ]));
});

it('produces a different canonical string (and signature) when only the app key changes', function () {
    $a = ServiceDeskSignature::canonical('satellite-1', 'POST', '/foo', '1700000000', 'abc123', 'body');
    $b = ServiceDeskSignature::canonical('satellite-2', 'POST', '/foo', '1700000000', 'abc123', 'body');

    expect($a)->not->toBe($b)
        ->and(ServiceDeskSignature::sign($a, 'shared-secret'))->not->toBe(ServiceDeskSignature::sign($b, 'shared-secret'));
});

it('signs a canonical string deterministically for the same secret', function () {
    $canonical = ServiceDeskSignature::canonical('satellite-1', 'POST', '/foo', '1700000000', 'abc123', 'body');

    expect(ServiceDeskSignature::sign($canonical, 'secret'))
        ->toBe('sha256='.hash_hmac('sha256', $canonical, 'secret'))
        ->and(ServiceDeskSignature::sign($canonical, 'secret'))->toBe(ServiceDeskSignature::sign($canonical, 'secret'))
        ->and(ServiceDeskSignature::sign($canonical, 'other-secret'))->not->toBe(ServiceDeskSignature::sign($canonical, 'secret'));
});

it('generates a 32 hex-char nonce that differs between calls', function () {
    $a = ServiceDeskSignature::generateNonce();
    $b = ServiceDeskSignature::generateNonce();

    expect($a)->toMatch('/^[a-f0-9]{32}$/')
        ->and($b)->toMatch('/^[a-f0-9]{32}$/')
        ->and($a)->not->toBe($b);
});
