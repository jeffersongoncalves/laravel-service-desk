<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

interface EmailDriver
{
    /** @return array<int, array<string, mixed>> */
    public function poll(EmailChannel $channel): array;

    public function getDriverName(): string;

    /**
     * Verify the channel's configured credentials without changing anything.
     * Webhook-based drivers have no connection to test and return a
     * best-effort true; only the polling (IMAP) driver actually connects.
     */
    public function testConnection(EmailChannel $channel): bool;
}
