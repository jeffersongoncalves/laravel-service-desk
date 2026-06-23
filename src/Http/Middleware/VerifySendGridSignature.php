<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JeffersonGoncalves\WebhookSignatures\WebhookSignatureManager;
use Symfony\Component\HttpFoundation\Response;

class VerifySendGridSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUsername = config('service-desk.email.inbound.sendgrid.webhook_username');
        $expectedPassword = config('service-desk.email.inbound.sendgrid.webhook_password');

        if (! $expectedUsername || ! $expectedPassword) {
            abort(500, 'SendGrid webhook credentials are not configured.');
        }

        // Service Desk protects the SendGrid inbound parse webhook with HTTP
        // Basic Auth (username/password), which maps to the package's Basic-Auth
        // verifier (registered under the "postmark" provider key). The package's
        // dedicated SendGrid verifier targets the ECDSA-signed Event Webhook and
        // does not apply to this Basic-Auth protected route.
        $verifier = app(WebhookSignatureManager::class)->verifier('postmark');

        if (! $verifier->verify($request, $expectedUsername.':'.$expectedPassword)) {
            abort(403, 'Invalid SendGrid webhook credentials.');
        }

        return $next($request);
    }
}
