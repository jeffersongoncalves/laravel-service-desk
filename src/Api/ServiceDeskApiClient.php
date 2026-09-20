<?php

namespace JeffersonGoncalves\ServiceDesk\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\ServiceDesk\Exceptions\ServiceDeskApiException;

/**
 * Generic signed HTTP client for the ticket API -- knows nothing about
 * tickets specifically, just how to sign a request and turn a response into
 * either decoded JSON or a ServiceDeskApiException. ApiTicketTransport maps
 * status codes it has business context for (404/409) to the same exception
 * types the database transport throws; everything else surfaces here.
 *
 * No retry: retrying a timed-out write either risks a duplicate (a fresh
 * nonce makes it a distinct signed request) or gets rejected as a replay,
 * so a failed write is surfaced as-is rather than silently retried.
 */
class ServiceDeskApiClient
{
    public function __construct(
        protected string $baseUrl,
        protected string $appKey,
        protected string $secret,
        protected int $timeout = 10,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $requestUri = '/'.ltrim($path, '/');

        if ($query !== []) {
            $requestUri .= '?'.http_build_query($query);
        }

        return $this->send('GET', $requestUri, '');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        return $this->sendJson('POST', $path, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function patch(string $path, array $payload = []): array
    {
        return $this->sendJson('PATCH', $path, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function sendJson(string $method, string $path, array $payload): array
    {
        $requestUri = '/'.ltrim($path, '/');
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->send($method, $requestUri, $body);
    }

    /** @return array<string, mixed> */
    protected function send(string $method, string $requestUri, string $body): array
    {
        $signer = new ServiceDeskSigner($this->appKey, $this->secret);
        $headers = $signer->headersFor($method, $requestUri, $body);
        $headers['Accept'] = 'application/json';

        try {
            $request = Http::withHeaders($headers)->timeout($this->timeout);

            $response = $body === ''
                ? $request->send($method, $this->baseUrl.$requestUri)
                : $request->withBody($body, 'application/json')->send($method, $this->baseUrl.$requestUri);
        } catch (ConnectionException $e) {
            throw ServiceDeskApiException::unreachable($e->getMessage());
        }

        return $this->decode($response);
    }

    /** @return array<string, mixed> */
    protected function decode(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        if ($response->status() === 401) {
            // The signature-verification middleware only ever aborts with
            // 401, never 403, so this status is unambiguous: it means our
            // own signature/secret was rejected, not a business-logic denial.
            throw ServiceDeskApiException::signatureRejected();
        }

        if ($response->status() === 422) {
            throw ServiceDeskApiException::validationFailed($response->json('errors') ?? []);
        }

        // 403/404/409 land here too, carrying their status code -- the
        // caller (ApiTicketTransport) has the ticket/transition context to
        // remap those into UnauthorizedOperatorException/TicketNotFoundException/
        // InvalidStatusTransitionException, the same types the database
        // transport throws for the same failures.
        throw ServiceDeskApiException::failed($response->status(), (string) $response->body());
    }
}
