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

    /**
     * Same order as ServiceDeskServiceProvider::hasMigrations(). SQLite doesn't
     * enforce foreign keys at CREATE TABLE time, but MySQL/Postgres do, so
     * migrations must run in dependency order, not alphabetically by stub name.
     */
    private const MIGRATION_ORDER = [
        // Core
        'create_service_desk_departments_table',
        'create_service_desk_categories_table',
        'create_service_desk_tickets_table',
        'create_service_desk_ticket_comments_table',
        'create_service_desk_ticket_attachments_table',
        'create_service_desk_ticket_history_table',
        'create_service_desk_department_operator_table',
        'create_service_desk_ticket_watchers_table',
        'create_service_desk_canned_responses_table',
        'create_service_desk_email_channels_table',
        'create_service_desk_inbound_emails_table',
        // Tags
        'create_service_desk_tags_table',
        'create_service_desk_taggables_table',
        // SLA
        'create_service_desk_business_hours_schedules_table',
        'create_service_desk_business_hours_time_slots_table',
        'create_service_desk_holidays_table',
        'create_service_desk_sla_policies_table',
        'create_service_desk_sla_targets_table',
        'create_service_desk_ticket_sla_table',
        'create_service_desk_escalation_rules_table',
        // Knowledge Base
        'create_service_desk_kb_categories_table',
        'create_service_desk_kb_articles_table',
        'create_service_desk_kb_article_versions_table',
        'create_service_desk_kb_article_feedback_table',
        'create_service_desk_kb_article_relations_table',
        'create_service_desk_kb_article_ticket_table',
        // Service Catalog
        'create_service_desk_service_categories_table',
        'create_service_desk_services_table',
        'create_service_desk_service_form_fields_table',
        'create_service_desk_service_requests_table',
        'create_service_desk_service_request_approvals_table',
    ];

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $migrationPath = __DIR__.'/../database/migrations';
        $files = [];

        foreach (self::MIGRATION_ORDER as $index => $name) {
            $migrationFile = $migrationPath.'/'.sprintf('%03d_%s.php', $index, $name);

            if (! file_exists($migrationFile)) {
                copy($migrationPath.'/'.$name.'.php.stub', $migrationFile);
            }

            $files[] = $migrationFile;
        }

        $this->loadMigrationsFrom($migrationPath);

        $this->beforeApplicationDestroyed(function () use ($files) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });
    }
}
