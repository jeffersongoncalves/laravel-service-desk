# Laravel Service Desk

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-service-desk.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-service-desk)
[![GitHub Release](https://img.shields.io/github/v/release/jeffersongoncalves/laravel-service-desk?style=flat-square)](https://github.com/jeffersongoncalves/laravel-service-desk/releases/latest)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-service-desk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-service-desk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-service-desk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/laravel-service-desk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-service-desk.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-service-desk)

A complete, headless Service Desk package for Laravel featuring ticket management, SLA tracking, knowledge base, service catalog with approval workflows, and multi-channel email integration.

## Features

- **Ticket Management** - Full lifecycle with status transitions, priorities, assignments, watchers, and auto-generated reference numbers
- **SLA Management** - Policies with business hours, breach detection, near-breach warnings, pause/resume, and escalation rules
- **Knowledge Base** - Articles with versioning, publishing workflow, full-text search, feedback collection, and SEO fields
- **Service Catalog** - Dynamic form builder, multi-step approval workflows, and automatic ticket creation
- **Email Integration** - Inbound email processing via IMAP, Mailgun, SendGrid, Resend, and Postmark
- **Event-Driven** - 24 domain events for extensibility
- **Notifications** - 10 built-in notification classes with queue support
- **Translations** - English and Brazilian Portuguese included
- **Headless** - No built-in UI; integrate with Filament, Livewire, Inertia, or any frontend

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require jeffersongoncalves/laravel-service-desk
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag="service-desk-config"
php artisan vendor:publish --tag="service-desk-migrations"
```

Run the migrations:

```bash
php artisan migrate
```

Optionally publish the translation files:

```bash
php artisan vendor:publish --tag="service-desk-translations"
```

## Configuration

The configuration file `config/service-desk.php` allows you to customize:

### Models

```php
'models' => [
    'user' => \App\Models\User::class,     // Who creates tickets
    'operator' => \App\Models\User::class,  // Who handles tickets
],
```

### Ticket Settings

```php
'ticket' => [
    'reference_prefix' => 'SD',            // Ticket references: SD-00001
    'default_status' => 'open',
    'default_priority' => 'medium',
    'allowed_extensions' => ['jpg', 'png', 'pdf', 'doc', 'xlsx', ...],
    'max_file_size' => 10240,              // KB
    'max_attachments_per_comment' => 5,
    'attachment_disk' => 'local',          // Any Laravel filesystem disk
    'auto_close_days' => null,             // null = disabled
    'allow_reopen' => true,
],
```

### SLA Settings

```php
'sla' => [
    'enabled' => true,
    'auto_apply' => true,                  // Auto-apply policies on ticket creation
    'near_breach_minutes' => 30,
    'pause_on_statuses' => ['on_hold'],
    'default_business_hours_schedule' => null,
],
```

### Knowledge Base

```php
'knowledge_base' => [
    'enabled' => true,
    'versioning_enabled' => true,
    'feedback_enabled' => true,
    'track_views' => true,
    'search_engine' => 'database',         // Or custom implementation
],
```

### Service Catalog

```php
'service_catalog' => [
    'enabled' => true,
    'auto_create_ticket' => true,          // Auto-create ticket from requests
    'approval_enabled' => true,
],
```

### Email Integration

```php
'email' => [
    'enabled' => true,
    'subject_prefix' => '[Service Desk #:reference]',
    'threading_enabled' => true,

    'inbound' => [
        'driver' => env('SERVICE_DESK_INBOUND_DRIVER'), // imap|mailgun|sendgrid|resend|postmark
        // Driver-specific settings...
    ],
],
```

### Notifications

```php
'notifications' => [
    'channels' => ['mail'],
    'queue' => env('SERVICE_DESK_NOTIFICATION_QUEUE', 'default'),

    'notify_on' => [
        'ticket_created' => true,
        'ticket_assigned' => true,
        'ticket_status_changed' => true,
        'comment_added' => true,
        'sla_breached' => true,
        // ...
    ],
],
```

## Setup

### Preparing Your User Model

Add the `HasTickets` and `IsOperator` traits to your User model:

```php
use JeffersonGoncalves\ServiceDesk\Concerns\HasTickets;
use JeffersonGoncalves\ServiceDesk\Concerns\IsOperator;

class User extends Authenticatable
{
    use HasTickets, IsOperator;
}
```

## Usage

### Using the Facade

The `ServiceDesk` facade provides a clean API for all operations:

```php
use JeffersonGoncalves\ServiceDesk\Facades\ServiceDesk;
```

### Ticket Management

```php
// Create a ticket
$ticket = ServiceDesk::createTicket([
    'title' => 'Cannot login to the system',
    'body' => 'I get an error when trying to login...',
    'priority' => 'high',
    'department_id' => 1,
    'category_id' => 2,
], $user);

// Update a ticket
ServiceDesk::updateTicket($ticket, [
    'title' => 'Updated title',
    'priority' => 'urgent',
], $performer);

// Change status
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;

ServiceDesk::changeStatus($ticket, TicketStatus::InProgress, $operator);

// Assign to operator
ServiceDesk::assignTicket($ticket, $operator, $assignedBy);

// Close / Reopen
ServiceDesk::closeTicket($ticket, $performer);
ServiceDesk::reopenTicket($ticket, $performer);

// Find tickets
$ticket = ServiceDesk::findTicketByUuid('550e8400-e29b-41d4-a716-446655440000');
$ticket = ServiceDesk::findTicketByReference('SD-00001');

// Watchers
ServiceDesk::addWatcher($ticket, $user);
ServiceDesk::removeWatcher($ticket, $user);
```

### Comments

```php
// Add a public reply
ServiceDesk::addComment($ticket, $author, 'Thanks for reporting this!', [
    'attachments' => $uploadedFiles,  // Optional
]);

// Add an internal note (not visible to the user)
ServiceDesk::addNote($ticket, $operator, 'Escalating to tier 2 support.');
```

### Departments

```php
// Create a department
$department = ServiceDesk::createDepartment([
    'name' => 'IT Support',
    'description' => 'Handles IT-related tickets',
    'is_active' => true,
]);

// Manage operators
ServiceDesk::addOperator($department, $operator, 'manager');
ServiceDesk::removeOperator($department, $operator);
```

### Accessing Services Directly

For advanced operations, access the underlying services:

```php
$ticketService = ServiceDesk::tickets();
$commentService = ServiceDesk::comments();
$departmentService = ServiceDesk::departments();
$attachmentService = ServiceDesk::attachments();
```

### Working with Enums

All enums support translated labels via the `label()` method:

```php
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\CommentType;

TicketStatus::Open->label();      // "Open" (en) / "Aberto" (pt_BR)
TicketPriority::High->label();    // "High" (en) / "Alta" (pt_BR)
CommentType::Reply->label();      // "Reply" (en) / "Resposta" (pt_BR)

// Status transitions
TicketStatus::Open->allowedTransitions();     // [Pending, InProgress, OnHold, ...]
TicketStatus::Open->canTransitionTo(TicketStatus::InProgress); // true
TicketStatus::Closed->canTransitionTo(TicketStatus::InProgress); // false

// Priority numeric value (useful for sorting)
TicketPriority::Low->numericValue();    // 1
TicketPriority::Urgent->numericValue(); // 4
```

### Available Enums

| Enum | Cases |
|------|-------|
| `TicketStatus` | Open, Pending, InProgress, OnHold, Resolved, Closed |
| `TicketPriority` | Low, Medium, High, Urgent |
| `TicketSource` | Web, Email, Api, ServiceRequest, Phone, Chat |
| `CommentType` | Reply, Note, System |
| `HistoryAction` | Created, StatusChanged, PriorityChanged, Assigned, ... |
| `ArticleStatus` | Draft, Published, Archived |
| `ArticleVisibility` | Public, Internal |
| `ServiceCategoryVisibility` | Public, Internal, Draft |
| `ServiceRequestStatus` | Pending, Approved, Rejected, InProgress, Fulfilled, Cancelled |
| `ApprovalStatus` | Pending, Approved, Rejected |
| `FormFieldType` | Text, Textarea, Select, Checkbox, Radio, Date, ... |
| `SlaBreachType` | FirstResponse, NextResponse, Resolution |
| `EscalationAction` | Notify, Reassign, ChangePriority, Custom |
| `DayOfWeek` | Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday |

## Events

The package dispatches domain events that you can listen to:

| Event | Dispatched When |
|-------|----------------|
| `TicketCreated` | A new ticket is created |
| `TicketUpdated` | A ticket is updated |
| `TicketStatusChanged` | Ticket status changes |
| `TicketAssigned` | Ticket is assigned to an operator |
| `TicketPriorityChanged` | Ticket priority changes |
| `TicketClosed` | A ticket is closed |
| `TicketReopened` | A closed ticket is reopened |
| `TicketDeleted` | A ticket is deleted |
| `CommentAdded` | A comment is added to a ticket |
| `AttachmentAdded` | An attachment is uploaded |
| `AttachmentRemoved` | An attachment is removed |
| `SlaApplied` | An SLA policy is applied to a ticket |
| `SlaBreached` | An SLA target is breached |
| `SlaNearBreach` | An SLA target is near breach |
| `SlaMetricMet` | An SLA target is met |
| `EscalationTriggered` | An escalation rule fires |
| `ArticleCreated` | A knowledge base article is created |
| `ArticlePublished` | An article is published |
| `ArticleFeedbackReceived` | Article feedback is submitted |
| `ServiceRequestCreated` | A service request is submitted |
| `ServiceRequestStatusChanged` | Service request status changes |
| `ApprovalRequested` | Approval is requested for a service request |
| `ApprovalDecisionMade` | An approval decision is made |
| `InboundEmailReceived` | An inbound email is received |
| `InboundEmailProcessed` | An inbound email is processed |

### Listening to Events

```php
// In your EventServiceProvider or listener
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;

class NotifySlackOnTicketCreation
{
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;
        // Send Slack notification...
    }
}
```

## Artisan Commands

| Command | Description |
|---------|-------------|
| `service-desk:poll-imap-mailbox` | Poll IMAP mailbox for new emails |
| `service-desk:clean-inbound-emails` | Delete old inbound emails past retention |
| `service-desk:close-stale-tickets` | Auto-close tickets without recent activity |
| `service-desk:check-sla-breaches` | Detect and mark SLA breaches |
| `service-desk:process-escalations` | Execute escalation rules for breached tickets |
| `service-desk:recalculate-sla` | Recalculate SLA due dates |

### Scheduling Commands

Add these to your `routes/console.php` or scheduler:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('service-desk:check-sla-breaches')->everyFiveMinutes();
Schedule::command('service-desk:process-escalations')->everyFiveMinutes();
Schedule::command('service-desk:poll-imap-mailbox')->everyFiveMinutes();
Schedule::command('service-desk:close-stale-tickets')->daily();
Schedule::command('service-desk:clean-inbound-emails')->daily();
```

## Email Integration

### IMAP Polling

Requires the optional `webklex/php-imap` package:

```bash
composer require webklex/php-imap
```

Configure in `.env`:

```env
SERVICE_DESK_INBOUND_DRIVER=imap
SERVICE_DESK_IMAP_HOST=imap.example.com
SERVICE_DESK_IMAP_PORT=993
SERVICE_DESK_IMAP_ENCRYPTION=ssl
SERVICE_DESK_IMAP_USERNAME=support@example.com
SERVICE_DESK_IMAP_PASSWORD=your-password
SERVICE_DESK_IMAP_FOLDER=INBOX
```

### Webhook Drivers (Mailgun, SendGrid, Resend, Postmark)

Configure the webhook driver and point your email provider's webhook URL to:

```
https://your-app.com/service-desk/webhooks/{driver}
```

Where `{driver}` is `mailgun`, `sendgrid`, `resend`, or `postmark`.

Each driver includes signature verification middleware for security.

## Translations

The package ships with English and Brazilian Portuguese translations. To customize or add new languages, publish the translation files:

```bash
php artisan vendor:publish --tag="service-desk-translations"
```

Translation files will be published to `lang/vendor/service-desk/`.

## Testing

```bash
composer test
```

### Static Analysis

```bash
composer analyse
```

### Code Formatting

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jefferson Goncalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
