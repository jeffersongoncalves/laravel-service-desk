<?php

namespace JeffersonGoncalves\ServiceDesk\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\PestPluginDatabaseMigrations\LoadsPackageMigrations;
use JeffersonGoncalves\ServiceDesk\ServiceDeskServiceProvider;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use LoadsPackageMigrations;
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            ServiceDeskServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $this->testing_connection());

        $app['config']->set('service-desk.models.user', User::class);
        $app['config']->set('service-desk.models.operator', User::class);
        $app['config']->set('service-desk.register_default_listeners', false);
    }

    /**
     * Defaults to an in-memory SQLite connection for local development; CI
     * (tests.yml) sets SERVICE_DESK_TEST_DB_* to run the same suite against
     * real MySQL and PostgreSQL instances too. Deliberately not the plain
     * DB_* names: Orchestra Testbench itself sets DB_CONNECTION=testing by
     * convention, which would collide with (and always win over) a driver
     * value read from the same variable here.
     *
     * @return array<string, mixed>
     */
    protected function testing_connection(): array
    {
        $driver = env('SERVICE_DESK_TEST_DB_DRIVER', 'sqlite');

        if ($driver === 'sqlite') {
            return ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        }

        return [
            'driver' => $driver,
            'host' => env('SERVICE_DESK_TEST_DB_HOST', '127.0.0.1'),
            'port' => env('SERVICE_DESK_TEST_DB_PORT'),
            'database' => env('SERVICE_DESK_TEST_DB_DATABASE', 'testing'),
            'username' => env('SERVICE_DESK_TEST_DB_USERNAME', 'root'),
            'password' => env('SERVICE_DESK_TEST_DB_PASSWORD', ''),
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'prefix' => '',
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadPackageMigrations(ServiceDeskServiceProvider::class);
    }
}
