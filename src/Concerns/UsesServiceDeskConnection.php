<?php

namespace JeffersonGoncalves\ServiceDesk\Concerns;

/**
 * Routes a model to config('service-desk.connection') instead of the app's
 * default connection -- opt-in via that config key, so it's a no-op unless
 * an app has actually chosen the shared-database strategy over the api
 * transport. A model-level `$connection` override still wins if one is set.
 */
trait UsesServiceDeskConnection
{
    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('service-desk.connection');
    }
}
