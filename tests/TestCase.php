<?php

namespace JeffersonGoncalves\ServiceDesk\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JeffersonGoncalves\ServiceDesk\ServiceDeskServiceProvider;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

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

        $stubsPath = __DIR__.'/../database/migrations';
        $tempPath = sys_get_temp_dir().'/laravel-service-desk-migrations';

        if (! is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        foreach (self::MIGRATION_ORDER as $index => $name) {
            copy($stubsPath.'/'.$name.'.php.stub', $tempPath.'/'.sprintf('%03d_%s.php', $index, $name));
        }

        $this->loadMigrationsFrom($tempPath);
    }
}
