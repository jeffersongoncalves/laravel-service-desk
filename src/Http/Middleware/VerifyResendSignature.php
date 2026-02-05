<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyResendSignature
{
    protected const TIMESTAMP_TOLERANCE = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $webhookSecret = config('service-desk.email.inbound.resend.webhook_secret');

        if (! $webhookSecret) {
            abort(500, 'Resend webhook secret is not configured.');
        }

        $svixId = $request->header('svix-id');
        $svixTimestamp = $request->header('svix-timestamp');
        $svixSignature = $request->header('svix-signature');

        if (! $svixId || ! $svixTimestamp || ! $svixSignature) {
            abort(403, 'Invalid Resend signature: missing Svix headers.');
        }

        $currentTimestamp = time();
        $webhookTimestamp = (int) $svixTimestamp;

        if (abs($currentTimestamp - $webhookTimestamp) > self::TIMESTAMP_TOLERANCE) {
            abort(403, 'Invalid Resend signature: timestamp outside tolerance.');
        }

        $body = $request->getContent();
        $signedContent = "{$svixId}.{$svixTimestamp}.{$body}";

        $secret = $webhookSecret;

        if (str_starts_with($secret, 'whsec_')) {
            $secret = substr($secret, 6);
        }

        $secretBytes = base64_decode($secret);
        $computedSignature = base64_encode(
            hash_hmac('sha256', $signedContent, $secretBytes, true)
        );

        $signatures = explode(' ', $svixSignature);
        $verified = false;

        foreach ($signatures as $sig) {
            $parts = explode(',', $sig, 2);
            $sigValue = $parts[1] ?? $parts[0];

            if (hash_equals($computedSignature, $sigValue)) {
                $verified = true;

                break;
            }
        }

        if (! $verified) {
            abort(403, 'Invalid Resend signature.');
        }

        return $next($request);
    }
}
