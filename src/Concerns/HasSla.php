<?php

namespace JeffersonGoncalves\ServiceDesk\Concerns;

use JeffersonGoncalves\ServiceDesk\Models\SlaPolicy;
use JeffersonGoncalves\ServiceDesk\Services\SlaService;

trait HasSla
{
    public function applySla(?SlaPolicy $policy = null): void
    {
        /** @var SlaService $slaService */
        $slaService = app(SlaService::class);

        $slaService->applyPolicy($this, $policy);
    }
}
