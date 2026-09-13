<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

interface EmailDriver
{
    /** @return array<int, array<string, mixed>> */
    public function poll(EmailChannel $channel): array;

    public function getDriverName(): string;
}
