<?php

namespace JeffersonGoncalves\ServiceDesk;

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Commands\CheckSlaBreachesCommand;
use JeffersonGoncalves\ServiceDesk\Commands\CleanInboundEmailsCommand;
use JeffersonGoncalves\ServiceDesk\Commands\CloseStaleTicketsCommand;
use JeffersonGoncalves\ServiceDesk\Commands\PollImapMailboxCommand;
use JeffersonGoncalves\ServiceDesk\Commands\ProcessEscalationsCommand;
use JeffersonGoncalves\ServiceDesk\Commands\RecalculateSlaCommand;
use JeffersonGoncalves\ServiceDesk\Events\CommentAdded;
use JeffersonGoncalves\ServiceDesk\Events\InboundEmailReceived;
use JeffersonGoncalves\ServiceDesk\Events\TicketAssigned;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Events\TicketStatusChanged;
use JeffersonGoncalves\ServiceDesk\Listeners\LogTicketHistory;
use JeffersonGoncalves\ServiceDesk\Listeners\ProcessInboundEmail;
use JeffersonGoncalves\ServiceDesk\Listeners\SendCommentAddedNotification;
use JeffersonGoncalves\ServiceDesk\Listeners\SendTicketAssignedNotification;
use JeffersonGoncalves\ServiceDesk\Listeners\SendTicketCreatedNotification;
use JeffersonGoncalves\ServiceDesk\Listeners\SendTicketStatusChangedNotification;
use JeffersonGoncalves\ServiceDesk\Services\AttachmentService;
use JeffersonGoncalves\ServiceDesk\Services\CommentService;
use JeffersonGoncalves\ServiceDesk\Services\DepartmentService;
use JeffersonGoncalves\ServiceDesk\Services\InboundEmailService;
use JeffersonGoncalves\ServiceDesk\Services\TicketService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServiceDeskServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('service-desk')
            ->hasConfigFile()
            ->hasMigrations([
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
            ])
            ->hasTranslations()
            ->hasRoute('webhooks')
            ->hasCommands([
                PollImapMailboxCommand::class,
                CleanInboundEmailsCommand::class,
                CloseStaleTicketsCommand::class,
                CheckSlaBreachesCommand::class,
                ProcessEscalationsCommand::class,
                RecalculateSlaCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TicketService::class);
        $this->app->singleton(CommentService::class);
        $this->app->singleton(DepartmentService::class);
        $this->app->singleton(AttachmentService::class);
        $this->app->singleton(InboundEmailService::class);

        $this->app->singleton(ServiceDeskManager::class, function ($app) {
            return new ServiceDeskManager(
                $app->make(TicketService::class),
                $app->make(CommentService::class),
                $app->make(DepartmentService::class),
                $app->make(AttachmentService::class),
            );
        });
    }

    public function packageBooted(): void
    {
        if (config('service-desk.register_default_listeners', true)) {
            $this->registerEventListeners();
        }
    }

    protected function registerEventListeners(): void
    {
        Event::subscribe(LogTicketHistory::class);

        Event::listen(TicketCreated::class, SendTicketCreatedNotification::class);
        Event::listen(TicketStatusChanged::class, SendTicketStatusChangedNotification::class);
        Event::listen(CommentAdded::class, SendCommentAddedNotification::class);
        Event::listen(TicketAssigned::class, SendTicketAssignedNotification::class);

        Event::listen(InboundEmailReceived::class, ProcessInboundEmail::class);
    }
}
