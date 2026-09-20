<?php

namespace JeffersonGoncalves\ServiceDesk\Api;

use Illuminate\Http\Request;
use JeffersonGoncalves\WebhookSignatures\Contracts\SignatureVerifier;

/**
 * Server-side half of the HMAC contract. Pure function of (request, secret)
 * -- knows nothing about which app or which of its secrets is being tried;
 * VerifyServiceDeskApiSignature loops over a client's registered secrets
 * (rotation) and calls this once per candidate.
 */
class ServiceDeskSignatureVerifier implements SignatureVerifier
{
    public function __construct(protected int $tolerance = 300) {}

    public function verify(Request $request, string $secret): bool
    {
        $appKey = $request->header(ServiceDeskSignature::HEADER_APP);
        $timestamp = $request->header(ServiceDeskSignature::HEADER_TIMESTAMP);
        $nonce = $request->header(ServiceDeskSignature::HEADER_NONCE);
        $signature = $request->header(ServiceDeskSignature::HEADER_SIGNATURE);

        if (! is_string($appKey) || $appKey === ''
            || ! is_string($timestamp) || ! ctype_digit($timestamp)
            || ! is_string($nonce) || ! preg_match('/^[a-f0-9]{32}$/', $nonce)
            || ! is_string($signature) || $signature === ''
        ) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $this->tolerance) {
            return false;
        }

        $canonical = ServiceDeskSignature::canonical(
            $request->method(),
            $request->getRequestUri(),
            $timestamp,
            $nonce,
            (string) $request->getContent(),
        );

        return hash_equals(ServiceDeskSignature::sign($canonical, $secret), $signature);
    }
}
