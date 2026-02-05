<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

interface EmailDriver
{
    public function poll(EmailChannel $channel): array;

    public function getDriverName(): string;
}
