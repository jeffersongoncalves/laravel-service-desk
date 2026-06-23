<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JeffersonGoncalves\WebhookSignatures\WebhookSignatureManager;
use Symfony\Component\HttpFoundation\Response;

class VerifyResendSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $webhookSecret = config('service-desk.email.inbound.resend.webhook_secret');

        if (! $webhookSecret) {
            abort(500, 'Resend webhook secret is not configured.');
        }

        $verifier = app(WebhookSignatureManager::class)->verifier('resend');

        if (! $verifier->verify($request, $webhookSecret)) {
            abort(403, 'Invalid Resend signature.');
        }

        return $next($request);
    }
}
