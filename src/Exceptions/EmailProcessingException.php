<?php

namespace JeffersonGoncalves\ServiceDesk\Exceptions;

use RuntimeException;

class EmailProcessingException extends RuntimeException
{
    public static function parsingFailed(string $reason): self
    {
        return new self("Failed to parse inbound email: {$reason}");
    }

    public static function driverNotInstalled(string $driver, string $package): self
    {
        return new self("The [{$driver}] email driver requires the [{$package}] package. Please install it.");
    }

    public static function connectionFailed(string $error): self
    {
        return new self("Email connection failed: {$error}");
    }
}
