<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use JeffersonGoncalves\ServiceDesk\Models\InboundEmail;

interface InboundEmailHandler
{
    public function handle(InboundEmail $inboundEmail): void;
}
