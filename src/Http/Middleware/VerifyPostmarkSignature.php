<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JeffersonGoncalves\WebhookSignatures\WebhookSignatureManager;
use Symfony\Component\HttpFoundation\Response;

class VerifyPostmarkSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUsername = config('service-desk.email.inbound.postmark.webhook_username');
        $expectedPassword = config('service-desk.email.inbound.postmark.webhook_password');

        if (! $expectedUsername || ! $expectedPassword) {
            abort(500, 'Postmark webhook credentials are not configured.');
        }

        // Postmark authenticates inbound webhooks via HTTP Basic Auth; the
        // package verifier expects the credential pair as "username:password".
        $verifier = app(WebhookSignatureManager::class)->verifier('postmark');

        if (! $verifier->verify($request, $expectedUsername.':'.$expectedPassword)) {
            abort(403, 'Invalid Postmark webhook credentials.');
        }

        return $next($request);
    }
}
