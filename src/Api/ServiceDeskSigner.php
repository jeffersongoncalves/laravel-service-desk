<?php

namespace JeffersonGoncalves\ServiceDesk\Api;

/**
 * Client-side half of the HMAC contract: builds the headers a satellite app
 * attaches to an outgoing request to the central ticket API.
 */
class ServiceDeskSigner
{
    public function __construct(
        protected string $appKey,
        protected string $secret,
    ) {}

    /** @return array<string, string> */
    public function headersFor(string $method, string $requestUri, string $body): array
    {
        $timestamp = (string) time();
        $nonce = ServiceDeskSignature::generateNonce();
        $canonical = ServiceDeskSignature::canonical($this->appKey, $method, $requestUri, $timestamp, $nonce, $body);

        return [
            ServiceDeskSignature::HEADER_APP => $this->appKey,
            ServiceDeskSignature::HEADER_TIMESTAMP => $timestamp,
            ServiceDeskSignature::HEADER_NONCE => $nonce,
            ServiceDeskSignature::HEADER_SIGNATURE => ServiceDeskSignature::sign($canonical, $this->secret),
        ];
    }
}
