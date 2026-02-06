<?php

namespace JeffersonGoncalves\ServiceDesk\Tests;

use JeffersonGoncalves\ServiceDesk\ServiceDeskServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ServiceDeskServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        config()->set('service-desk.models.user', \JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User::class);
        config()->set('service-desk.models.operator', \JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User::class);
        config()->set('service-desk.register_default_listeners', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->runPackageMigrations();
    }

    protected function runPackageMigrations(): void
    {
        $migrationPath = __DIR__.'/../database/migrations';

        $stubs = glob($migrationPath.'/*.php.stub');

        sort($stubs);

        foreach ($stubs as $stub) {
            $migration = require $stub;
            $migration->up();
        }
    }
}
