<?php

namespace JeffersonGoncalves\ServiceDesk\Exceptions;

use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use RuntimeException;

/**
 * Failures specific to the API ticket transport that have no equivalent on
 * the database transport -- a local DB call can't be "unreachable" or
 * return a signature error. Failures both transports can hit (not found,
 * invalid transition, unauthorized) stay on their existing exception types
 * so callers don't have to branch on which transport is configured.
 */
class ServiceDeskApiException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null)
    {
        parent::__construct($message);
    }

    public static function notConfigured(string $key): self
    {
        return new self("Service Desk API transport is not configured: missing [{$key}].");
    }

    public static function unreachable(string $message): self
    {
        return new self("Service Desk API is unreachable: {$message}");
    }

    public static function signatureRejected(): self
    {
        return new self('Service Desk API rejected our request signature -- check the configured app key and secret.');
    }

    public static function operatorOnly(string $action): self
    {
        return new self("[{$action}] is an operator-only action and has no endpoint on the API transport; use the database transport for operator consoles.");
    }

    public static function statusNotSupported(TicketStatus $status): self
    {
        return new self("The API transport only supports closing/reopening tickets remotely, not transitioning to [{$status->value}].");
    }

    /** @param  array<string, mixed>  $errors */
    public static function validationFailed(array $errors): self
    {
        return new self('Service Desk API rejected the request: '.json_encode($errors));
    }

    public static function failed(int $status, string $body): self
    {
        return new self("Service Desk API request failed with status [{$status}]: {$body}", $status);
    }
}
