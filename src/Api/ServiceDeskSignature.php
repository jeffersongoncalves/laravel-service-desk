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
     * The app key is part of the signed payload so a request signed for one
     * client can't be re-labeled as another client's by just changing the
     * X-Service-Desk-App header -- without it, two clients that happen to
     * share a secret (a misconfiguration, but one the format shouldn't make
     * exploitable) could impersonate each other. The method and path are
     * signed too, so a valid signature for one endpoint can't be replayed
     * against another; the body is folded to a fixed-size hash rather than
     * signed inline.
     */
    public static function canonical(string $appKey, string $method, string $requestUri, string $timestamp, string $nonce, string $body): string
    {
        return implode("\n", [
            $appKey,
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
