<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskApiClient;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSignature;
use JeffersonGoncalves\ServiceDesk\Exceptions\ServiceDeskApiException;

beforeEach(function () {
    $this->client = new ServiceDeskApiClient('https://central.test', 'satellite-1', 'secret', 5);
});

it('signs GET requests and decodes the JSON body', function () {
    Http::fake([
        'central.test/*' => Http::response(['data' => ['uuid' => 'abc']], 200),
    ]);

    $result = $this->client->get('tickets/abc');

    expect($result)->toBe(['data' => ['uuid' => 'abc']]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://central.test/tickets/abc'
            && $request->method() === 'GET'
            && $request->hasHeader(ServiceDeskSignature::HEADER_APP, 'satellite-1')
            && $request->hasHeader(ServiceDeskSignature::HEADER_SIGNATURE);
    });
});

it('appends query params to the request uri for GET', function () {
    Http::fake(['central.test/*' => Http::response(['data' => []], 200)]);

    $this->client->get('tickets', ['reference' => 'SD-00001']);

    Http::assertSent(fn ($request) => $request->url() === 'https://central.test/tickets?reference=SD-00001');
});

it('sends a signed JSON body for POST/PATCH', function () {
    Http::fake(['central.test/*' => Http::response(['data' => ['uuid' => 'abc']], 200)]);

    $this->client->post('tickets', ['title' => 'Hi']);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://central.test/tickets'
            && $request['title'] === 'Hi';
    });
});

it('throws a ConnectionException-derived exception when the API is unreachable', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    expect(fn () => $this->client->get('tickets/abc'))
        ->toThrow(ServiceDeskApiException::class, 'Service Desk API is unreachable: Connection timed out');
});

it('throws signatureRejected on 401', function () {
    Http::fake(['central.test/*' => Http::response(['message' => 'Invalid signature.'], 401)]);

    expect(fn () => $this->client->get('tickets/abc'))->toThrow(ServiceDeskApiException::class);
});

it('throws validationFailed on 422 carrying the errors payload', function () {
    Http::fake(['central.test/*' => Http::response(['errors' => ['title' => ['required']]], 422)]);

    try {
        $this->client->post('tickets', []);
        expect(false)->toBeTrue('expected exception was not thrown');
    } catch (ServiceDeskApiException $e) {
        expect($e->getMessage())->toContain('title');
    }
});

it('carries the status code on the exception for 404/409 so callers can remap it', function () {
    Http::fake(['central.test/*' => Http::response(['message' => 'not found'], 404)]);

    try {
        $this->client->get('tickets/missing');
        expect(false)->toBeTrue('expected exception was not thrown');
    } catch (ServiceDeskApiException $e) {
        expect($e->status)->toBe(404);
    }
});
