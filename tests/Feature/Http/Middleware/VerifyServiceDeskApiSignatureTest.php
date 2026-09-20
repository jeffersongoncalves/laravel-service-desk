<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSignature;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSigner;
use JeffersonGoncalves\ServiceDesk\Http\Middleware\VerifyServiceDeskApiSignature;

beforeEach(function () {
    config()->set('service-desk.api.clients', [
        'satellite-1' => ['secrets' => ['secret-a', 'secret-b']],
    ]);

    Route::post('/__test/service-desk-api', fn () => response()->json(['ok' => true]))
        ->middleware(VerifyServiceDeskApiSignature::class);
});

$callWithSignature = function (string $appKey, string $secret, array $payload = ['title' => 'Hi']) {
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signer = new ServiceDeskSigner($appKey, $secret);
    $headers = $signer->headersFor('POST', '/__test/service-desk-api', $body);

    return test()->withHeaders($headers)->postJson('/__test/service-desk-api', $payload);
};

it('allows a request signed with a registered secret', function () use ($callWithSignature) {
    $callWithSignature('satellite-1', 'secret-a')->assertOk()->assertJson(['ok' => true]);
});

it('allows a request signed with the rotated (second) secret', function () use ($callWithSignature) {
    $callWithSignature('satellite-1', 'secret-b')->assertOk();
});

it('rejects an unknown app key', function () use ($callWithSignature) {
    $callWithSignature('unknown-app', 'secret-a')->assertStatus(401);
});

it('rejects a request signed with a secret not registered for that app', function () use ($callWithSignature) {
    $callWithSignature('satellite-1', 'not-a-registered-secret')->assertStatus(401);
});

it('rejects a replayed request (same nonce twice)', function () {
    $body = ['title' => 'Replay me'];
    $signer = new ServiceDeskSigner('satellite-1', 'secret-a');
    $bodyJson = json_encode($body, JSON_THROW_ON_ERROR);
    $headers = $signer->headersFor('POST', '/__test/service-desk-api', $bodyJson);

    test()->withHeaders($headers)->postJson('/__test/service-desk-api', $body)->assertOk();
    test()->withHeaders($headers)->postJson('/__test/service-desk-api', $body)->assertStatus(401);
});

it('rejects a request re-labeled with another configured client sharing the same secret', function () {
    config()->set('service-desk.api.clients', [
        'satellite-1' => ['secrets' => ['shared-secret']],
        'satellite-2' => ['secrets' => ['shared-secret']],
    ]);

    $body = ['title' => 'Hi'];
    $bodyJson = json_encode($body, JSON_THROW_ON_ERROR);
    $signer = new ServiceDeskSigner('satellite-1', 'shared-secret');
    $headers = $signer->headersFor('POST', '/__test/service-desk-api', $bodyJson);
    $headers[ServiceDeskSignature::HEADER_APP] = 'satellite-2';

    test()->withHeaders($headers)->postJson('/__test/service-desk-api', $body)->assertStatus(401);
});

it('stamps the verified app key onto the request attributes', function () {
    Route::post('/__test/service-desk-api-attr', function (Request $request) {
        return response()->json(['app_key' => $request->attributes->get('service_desk_api_app_key')]);
    })->middleware(VerifyServiceDeskApiSignature::class);

    $body = ['title' => 'Hi'];
    $signer = new ServiceDeskSigner('satellite-1', 'secret-a');
    $headers = $signer->headersFor('POST', '/__test/service-desk-api-attr', json_encode($body, JSON_THROW_ON_ERROR));

    test()->withHeaders($headers)->postJson('/__test/service-desk-api-attr', $body)
        ->assertOk()
        ->assertJson(['app_key' => 'satellite-1']);
});
