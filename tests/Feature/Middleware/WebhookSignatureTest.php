<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JeffersonGoncalves\ServiceDesk\Http\Middleware\VerifyPostmarkSignature;
use JeffersonGoncalves\ServiceDesk\Http\Middleware\VerifySendGridSignature;
use Symfony\Component\HttpKernel\Exception\HttpException;

function basicAuthRequest(?string $user, ?string $password): Request
{
    $server = [];

    if ($user !== null) {
        $server['PHP_AUTH_USER'] = $user;
    }

    if ($password !== null) {
        $server['PHP_AUTH_PW'] = $password;
    }

    return Request::create('/service-desk/webhooks/test', 'POST', [], [], [], $server);
}

function runMiddleware(object $middleware, Request $request): Response
{
    return $middleware->handle($request, fn () => new Response('ok'));
}

function captureStatus(callable $callback): int
{
    try {
        $callback();
    } catch (HttpException $e) {
        return $e->getStatusCode();
    }

    return 200;
}

// ── Postmark ─────────────────────────────────────────────────────────────────

it('rejects Postmark webhook when credentials are not configured (fail-closed)', function () {
    config()->set('service-desk.email.inbound.postmark.webhook_username', null);
    config()->set('service-desk.email.inbound.postmark.webhook_password', null);

    $status = captureStatus(fn () => runMiddleware(new VerifyPostmarkSignature, basicAuthRequest('user', 'secret')));

    expect($status)->toBe(500);
});

it('rejects Postmark webhook with invalid credentials', function () {
    config()->set('service-desk.email.inbound.postmark.webhook_username', 'user');
    config()->set('service-desk.email.inbound.postmark.webhook_password', 'secret');

    $status = captureStatus(fn () => runMiddleware(new VerifyPostmarkSignature, basicAuthRequest('user', 'wrong')));

    expect($status)->toBe(403);
});

it('rejects Postmark webhook with missing credentials in the request', function () {
    config()->set('service-desk.email.inbound.postmark.webhook_username', 'user');
    config()->set('service-desk.email.inbound.postmark.webhook_password', 'secret');

    $status = captureStatus(fn () => runMiddleware(new VerifyPostmarkSignature, basicAuthRequest(null, null)));

    expect($status)->toBe(403);
});

it('allows Postmark webhook with valid credentials', function () {
    config()->set('service-desk.email.inbound.postmark.webhook_username', 'user');
    config()->set('service-desk.email.inbound.postmark.webhook_password', 'secret');

    $response = runMiddleware(new VerifyPostmarkSignature, basicAuthRequest('user', 'secret'));

    expect($response->getContent())->toBe('ok');
});

// ── SendGrid ─────────────────────────────────────────────────────────────────

it('rejects SendGrid webhook when credentials are not configured (fail-closed)', function () {
    config()->set('service-desk.email.inbound.sendgrid.webhook_username', null);
    config()->set('service-desk.email.inbound.sendgrid.webhook_password', null);

    $status = captureStatus(fn () => runMiddleware(new VerifySendGridSignature, basicAuthRequest('user', 'secret')));

    expect($status)->toBe(500);
});

it('rejects SendGrid webhook with invalid credentials', function () {
    config()->set('service-desk.email.inbound.sendgrid.webhook_username', 'user');
    config()->set('service-desk.email.inbound.sendgrid.webhook_password', 'secret');

    $status = captureStatus(fn () => runMiddleware(new VerifySendGridSignature, basicAuthRequest('user', 'wrong')));

    expect($status)->toBe(403);
});

it('allows SendGrid webhook with valid credentials', function () {
    config()->set('service-desk.email.inbound.sendgrid.webhook_username', 'user');
    config()->set('service-desk.email.inbound.sendgrid.webhook_password', 'secret');

    $response = runMiddleware(new VerifySendGridSignature, basicAuthRequest('user', 'secret'));

    expect($response->getContent())->toBe('ok');
});
