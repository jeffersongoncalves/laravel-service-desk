<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSignature;
use JeffersonGoncalves\ServiceDesk\Api\ServiceDeskSignatureVerifier;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the app-to-app HMAC signature on an incoming ticket API request.
 * One generic 401 for every failure mode (unknown app, bad signature, stale
 * timestamp, replayed nonce) -- never tells a caller which check failed, to
 * avoid handing an attacker a probing oracle.
 */
class VerifyServiceDeskApiSignature
{
    public function __construct(protected ServiceDeskSignatureVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header(ServiceDeskSignature::HEADER_APP);

        if (! is_string($appKey) || $appKey === '') {
            abort(401, 'Invalid signature.');
        }

        $secrets = config("service-desk.api.clients.{$appKey}.secrets", []);

        if (! is_array($secrets) || $secrets === [] || ! $this->matchesAnySecret($request, $secrets)) {
            abort(401, 'Invalid signature.');
        }

        if (! $this->claimNonce($appKey, (string) $request->header(ServiceDeskSignature::HEADER_NONCE))) {
            abort(401, 'Invalid signature.');
        }

        $request->attributes->set('service_desk_api_app_key', $appKey);

        return $next($request);
    }

    /** @param  array<int, mixed>  $secrets */
    protected function matchesAnySecret(Request $request, array $secrets): bool
    {
        foreach ($secrets as $secret) {
            if (is_string($secret) && $secret !== '' && $this->verifier->verify($request, $secret)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Atomically claims the nonce so the same signed request can't be
     * replayed; TTL outlives the timestamp tolerance window since a nonce
     * only needs remembering for as long as its signature would still pass
     * the freshness check.
     */
    protected function claimNonce(string $appKey, string $nonce): bool
    {
        $tolerance = (int) config('service-desk.api.tolerance', 300);

        return Cache::add("service-desk-api-nonce:{$appKey}:{$nonce}", true, $tolerance + 60);
    }
}
