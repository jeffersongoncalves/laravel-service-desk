<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMailgunSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signingKey = config('service-desk.email.inbound.mailgun.signing_key');

        if (! $signingKey) {
            abort(500, 'Mailgun signing key is not configured.');
        }

        $signature = $request->input('signature', []);
        $timestamp = $signature['timestamp'] ?? '';
        $token = $signature['token'] ?? '';
        $expectedSignature = $signature['signature'] ?? '';

        if (! $timestamp || ! $token || ! $expectedSignature) {
            abort(403, 'Invalid Mailgun signature: missing parameters.');
        }

        $computedSignature = hash_hmac('sha256', $timestamp.$token, $signingKey);

        if (! hash_equals($computedSignature, $expectedSignature)) {
            abort(403, 'Invalid Mailgun signature.');
        }

        return $next($request);
    }
}
