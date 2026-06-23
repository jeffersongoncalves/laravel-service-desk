<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JeffersonGoncalves\WebhookSignatures\WebhookSignatureManager;
use Symfony\Component\HttpFoundation\Response;

class VerifyMailgunSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signingKey = config('service-desk.email.inbound.mailgun.signing_key');

        if (! $signingKey) {
            abort(500, 'Mailgun signing key is not configured.');
        }

        $verifier = app(WebhookSignatureManager::class)->verifier('mailgun');

        if (! $verifier->verify($request, $signingKey)) {
            abort(403, 'Invalid Mailgun signature.');
        }

        return $next($request);
    }
}
