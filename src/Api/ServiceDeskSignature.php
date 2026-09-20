<?php

namespace JeffersonGoncalves\ServiceDesk\Api;

/**
 * Shared HMAC contract for the app-to-app ticket API: what gets signed, and
 * how. Both ServiceDeskSigner (client) and ServiceDeskSignatureVerifier
 * (server) build from this so they can never drift apart.
 */
final class ServiceDeskSignature
{
    public const HEADER_APP = 'X-Service-Desk-App';

    public const HEADER_TIMESTAMP = 'X-Service-Desk-Timestamp';

    public const HEADER_NONCE = 'X-Service-Desk-Nonce';

    public const HEADER_SIGNATURE = 'X-Service-Desk-Signature';

    /**
     * The method and path are part of the signed payload so a valid
     * signature for one endpoint can't be replayed against another; the
     * body is folded to a fixed-size hash rather than signed inline.
     */
    public static function canonical(string $method, string $requestUri, string $timestamp, string $nonce, string $body): string
    {
        return implode("\n", [
            strtoupper($method),
            $requestUri,
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]);
    }

    public static function sign(string $canonical, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $canonical, $secret);
    }

    public static function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }
}
