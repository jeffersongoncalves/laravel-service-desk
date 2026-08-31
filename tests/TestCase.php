<?php

namespace JeffersonGoncalves\ServiceDesk\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\ServiceDesk\ServiceDeskServiceProvider;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            ServiceDeskServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', match (env('DB_CONNECTION', 'sqlite')) {
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE', 'testing'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'prefix' => '',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', 5432),
                'database' => env('DB_DATABASE', 'testing'),
                'username' => env('DB_USERNAME', 'postgres'),
                'password' => env('DB_PASSWORD', 'postgres'),
                'prefix' => '',
            ],
            default => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        });

        config()->set('service-desk.models.user', User::class);
        config()->set('service-desk.models.operator', User::class);
        config()->set('service-desk.register_default_listeners', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $migrationPath = __DIR__.'/../database/migrations';
        $files = glob($migrationPath.'/*.php.stub');

        foreach ($files as $file) {
            $migrationFile = $migrationPath.'/'.basename($file, '.stub');

            if (! file_exists($migrationFile)) {
                copy($file, $migrationFile);
            }
        }

        $this->loadMigrationsFrom($migrationPath);

        $this->beforeApplicationDestroyed(function () use ($migrationPath, $files) {
            foreach ($files as $file) {
                $migrationFile = $migrationPath.'/'.basename($file, '.stub');

                if (file_exists($migrationFile)) {
                    unlink($migrationFile);
                }
            }
        });
    }
}
