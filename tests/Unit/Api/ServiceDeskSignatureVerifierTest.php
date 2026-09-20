<?php

use Illuminate\Http\Request;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSignature;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSignatureVerifier;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSigner;

$signedRequest = function (string $method, string $uri, string $body, string $appKey, string $secret): Request {
    $signer = new ServiceDeskSigner($appKey, $secret);
    $headers = $signer->headersFor($method, $uri, $body);

    $request = Request::create($uri, $method, [], [], [], [], $body);

    foreach ($headers as $name => $value) {
        $request->headers->set($name, $value);
    }

    return $request;
};

it('verifies a correctly signed request', function () use ($signedRequest) {
    $request = $signedRequest('POST', '/service-desk/api/tickets', '{"title":"Hi"}', 'satellite-1', 'secret');

    expect((new ServiceDeskSignatureVerifier)->verify($request, 'secret'))->toBeTrue();
});

it('rejects a request signed with a different secret', function () use ($signedRequest) {
    $request = $signedRequest('POST', '/service-desk/api/tickets', '{"title":"Hi"}', 'satellite-1', 'secret');

    expect((new ServiceDeskSignatureVerifier)->verify($request, 'wrong-secret'))->toBeFalse();
});

it('rejects a request whose body was tampered with after signing', function () use ($signedRequest) {
    $original = $signedRequest('POST', '/service-desk/api/tickets', '{"title":"Hi"}', 'satellite-1', 'secret');
    $tampered = Request::create($original->getRequestUri(), 'POST', [], [], [], [], '{"title":"Tampered"}');

    foreach ($original->headers->all() as $name => $values) {
        $tampered->headers->set($name, $values);
    }

    expect((new ServiceDeskSignatureVerifier)->verify($tampered, 'secret'))->toBeFalse();
});

it('rejects a stale timestamp outside the tolerance window', function () {
    $uri = '/service-desk/api/tickets';
    $timestamp = (string) (time() - 600);
    $nonce = str_repeat('a', 32);
    $canonical = ServiceDeskSignature::canonical('POST', $uri, $timestamp, $nonce, 'body');
    $signature = ServiceDeskSignature::sign($canonical, 'secret');

    $request = Request::create($uri, 'POST', [], [], [], [], 'body');
    $request->headers->set(ServiceDeskSignature::HEADER_APP, 'satellite-1');
    $request->headers->set(ServiceDeskSignature::HEADER_TIMESTAMP, $timestamp);
    $request->headers->set(ServiceDeskSignature::HEADER_NONCE, $nonce);
    $request->headers->set(ServiceDeskSignature::HEADER_SIGNATURE, $signature);

    expect((new ServiceDeskSignatureVerifier(tolerance: 300))->verify($request, 'secret'))->toBeFalse();
});

it('rejects a malformed nonce', function () use ($signedRequest) {
    $request = $signedRequest('POST', '/service-desk/api/tickets', 'body', 'satellite-1', 'secret');
    $request->headers->set(ServiceDeskSignature::HEADER_NONCE, 'not-hex!!');

    expect((new ServiceDeskSignatureVerifier)->verify($request, 'secret'))->toBeFalse();
});

it('rejects a request missing the app header', function () use ($signedRequest) {
    $request = $signedRequest('POST', '/service-desk/api/tickets', 'body', 'satellite-1', 'secret');
    $request->headers->remove(ServiceDeskSignature::HEADER_APP);

    expect((new ServiceDeskSignatureVerifier)->verify($request, 'secret'))->toBeFalse();
});
