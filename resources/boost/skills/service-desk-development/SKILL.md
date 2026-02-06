---
name: service-desk-development
description: Build and extend the Service Desk package including tickets, SLA management, knowledge base, service catalog, and email integration.
---

# Service Desk Development

## When to use this skill

Use this skill when:
- Creating or modifying tickets, comments, attachments, or departments
- Working with SLA policies, business hours, escalation rules, or breach detection
- Building knowledge base articles, categories, versioning, or search features
- Managing service catalog items, form fields, service requests, or approval workflows
- Integrating inbound email processing (IMAP, Mailgun, SendGrid, Resend, Postmark)
- Writing tests for the service desk package
- Extending the package with new models, services, events, or listeners

## Package Structure

```
src/
├── Commands/           # 6 Artisan commands (poll-imap, check-sla, etc.)
├── Concerns/           # HasTickets, IsOperator, HasSla traits
├── Contracts/          # 7 interfaces (EmailDriver, SlaCalculator, etc.)
├── Enums/              # 14 backed enums (TicketStatus, TicketPriority, etc.)
├── Events/             # 24 events across all modules
├── Exceptions/         # 4 custom exceptions
├── Facades/            # ServiceDesk facade
├── Http/
│   ├── Controllers/    # 4 webhook controllers (Mailgun, SendGrid, Resend, Postmark)
│   └── Middleware/     # 4 signature verification middleware
├── Listeners/          # 6 listeners + 1 subscriber
├── Mail/
│   ├── Drivers/        # 5 email drivers (Imap, Mailgun, SendGrid, Resend, Postmark)
│   ├── EmailParser.php
│   └── ThreadResolver.php
├── Models/             # 27 Eloquent models
├── Notifications/      # 10 notification classes
├── Services/           # 13 service classes
├── ServiceDeskManager.php
└── ServiceDeskServiceProvider.php
```

## Models (27 total)

### Core Models
- **Department** — `service_desk_departments`: Has categories, tickets, email channels, operators (pivot)
- **Category** — `service_desk_categories`: Belongs to department, self-referencing parent/children
- **Ticket** — `service_desk_tickets`: Central model with morphTo user/assignedTo, SLA fields, tags, linked articles
- **TicketComment** — `service_desk_ticket_comments`: Reply, Note, or System comment with optional email_message_id
- **TicketAttachment** — `service_desk_ticket_attachments`: File uploads with UUID, disk, mime_type
- **TicketHistory** — `service_desk_ticket_history`: Audit log of all ticket changes
- **TicketWatcher** — `service_desk_ticket_watchers`: Polymorphic watchers
- **CannedResponse** — `service_desk_canned_responses`: Pre-defined response templates
- **EmailChannel** — `service_desk_email_channels`: Inbound email configuration per driver
- **InboundEmail** — `service_desk_inbound_emails`: Raw inbound email storage with processing status

### Tags
- **Tag** — `service_desk_tags`: Polymorphic MorphToMany with tickets, articles, services

### SLA Models
- **BusinessHoursSchedule** — `service_desk_business_hours_schedules`: Named schedules with timezone
- **BusinessHoursTimeSlot** — `service_desk_business_hours_time_slots`: Day/time slots per schedule
- **Holiday** — `service_desk_holidays`: Holidays per schedule (recurring or one-time)
- **SlaPolicy** — `service_desk_sla_policies`: Policy with conditions JSON for auto-matching
- **SlaTarget** — `service_desk_sla_targets`: Per-priority response/resolution time targets in minutes
- **TicketSla** — `service_desk_ticket_sla`: Per-ticket SLA tracking with pause/resume/breach detection
- **EscalationRule** — `service_desk_escalation_rules`: Actions triggered on SLA breach (notify, reassign, etc.)

### Knowledge Base Models
- **KbCategory** — `service_desk_kb_categories`: Self-referencing with visibility (public/internal/draft)
- **KbArticle** — `service_desk_kb_articles`: With versioning, feedback, fullText search, SEO fields
- **KbArticleVersion** — `service_desk_kb_article_versions`: Immutable version snapshots
- **KbArticleFeedback** — `service_desk_kb_article_feedback`: Helpful/not-helpful with optional comment

### Service Catalog Models
- **ServiceCategory** — `service_desk_service_categories`: Self-referencing categories
- **Service** — `service_desk_services`: With SLA policy link, dynamic form fields, approval flag
- **ServiceFormField** — `service_desk_service_form_fields`: Dynamic form definition (type, validation, options)
- **ServiceRequest** — `service_desk_service_requests`: User submissions with form_data JSON
- **ServiceRequestApproval** — `service_desk_service_request_approvals`: Multi-step approval workflow

## Services

| Service | Purpose |
|---------|---------|
| `TicketService` | CRUD tickets, status transitions, assignment, close/reopen, merge |
| `CommentService` | Add replies, notes, system comments; process attachments |
| `DepartmentService` | CRUD departments, manage operators |
| `AttachmentService` | Upload, download, delete file attachments |
| `InboundEmailService` | Store inbound emails, deduplication, mark processed/failed/ignored |
| `TagService` | CRUD tags, sync/attach/detach on any taggable model |
| `SlaService` | Apply SLA policies, calculate due dates, check breaches |
| `BusinessHoursService` | Calculate business hours, add business minutes to timestamps |
| `EscalationService` | Execute escalation actions (notify, reassign, change priority) |
| `KnowledgeBaseService` | CRUD articles with versioning, publish, search, feedback |
| `ServiceCatalogService` | CRUD service categories, services, form fields |
| `ServiceRequestService` | Create requests, validate form data, create linked tickets |
| `ApprovalService` | Create approval steps, approve/reject with multi-step support |

## Creating a New Ticket

```php
use JeffersonGoncalves\ServiceDesk\Services\TicketService;

$ticketService = app(TicketService::class);

$ticket = $ticketService->create([
    'department_id' => 1,
    'title' => 'Server is down',
    'description' => 'Production server not responding.',
    'priority' => 'urgent',
    'source' => 'web',
], $user);

// Events dispatched: TicketCreated
// Auto-generated: uuid, reference_number (e.g., SD-000001)
// If SLA auto_apply is enabled, SLA policy is automatically matched and applied
```

## Status Transitions

```php
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;

// Valid transitions are enforced by TicketStatus::allowedTransitions()
// Open -> Pending, InProgress, OnHold, Resolved, Closed
// Pending -> Open, InProgress, OnHold, Resolved, Closed
// InProgress -> Pending, OnHold, Resolved, Closed
// OnHold -> Open, Pending, InProgress (pauses SLA)
// Resolved -> Open (reopen), Closed
// Closed -> Open (if config allow_reopen is true)

$ticketService->changeStatus($ticket, TicketStatus::InProgress, $operator);
```

## SLA Workflow

```php
use JeffersonGoncalves\ServiceDesk\Services\SlaService;

$slaService = app(SlaService::class);

// 1. Create business hours schedule
$schedule = BusinessHoursSchedule::create([
    'name' => 'Standard Business Hours',
    'timezone' => 'America/Sao_Paulo',
    'is_default' => true,
]);

// 2. Add time slots
BusinessHoursTimeSlot::create([
    'schedule_id' => $schedule->id,
    'day_of_week' => 1, // Monday
    'start_time' => '09:00',
    'end_time' => '18:00',
]);

// 3. Create SLA policy with priority targets
$policy = SlaPolicy::create([
    'name' => 'Standard SLA',
    'business_hours_schedule_id' => $schedule->id,
    'conditions' => ['priorities' => ['high', 'urgent']],
]);

SlaTarget::create([
    'sla_policy_id' => $policy->id,
    'priority' => 'urgent',
    'first_response_time' => 30,   // 30 business minutes
    'resolution_time' => 240,       // 4 business hours
]);

// 4. SLA is auto-applied when ticket is created (if sla.auto_apply is true)
// 5. Scheduler runs service-desk:check-sla every minute to detect breaches
```

## Events

Events dispatched by the package — subscribe via Laravel's event system or the built-in listeners:

**Ticket:** TicketCreated, TicketUpdated, TicketStatusChanged, TicketPriorityChanged, TicketAssigned, TicketClosed, TicketReopened, TicketDeleted, TicketMerged, TicketTagsChanged
**Comment:** CommentAdded
**Attachment:** AttachmentAdded, AttachmentRemoved
**Email:** InboundEmailReceived, InboundEmailProcessed
**SLA:** SlaApplied, SlaBreached, SlaNearBreach, SlaMetricMet, EscalationTriggered
**KB:** ArticleCreated, ArticlePublished, ArticleFeedbackReceived
**Catalog:** ServiceRequestCreated, ServiceRequestStatusChanged, ApprovalRequested, ApprovalDecisionMade

## Testing Patterns

```php
// Feature tests use Orchestra Testbench with SQLite in-memory
// The TestCase loads package migrations via runPackageMigrations()

use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

it('can create a ticket', function () {
    $user = User::create(['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret']);
    $department = Department::create(['name' => 'IT', 'slug' => 'it']);

    $ticket = app(TicketService::class)->create([
        'department_id' => $department->id,
        'title' => 'Test Ticket',
        'description' => 'Description',
    ], $user);

    expect($ticket)->toBeInstanceOf(Ticket::class)
        ->and($ticket->reference_number)->toStartWith('SD-');
});

// When testing events, use Event::fake() with specific event classes
// to avoid blocking Eloquent model events (uuid/reference generation)
Event::fake([TicketCreated::class, TicketStatusChanged::class]);
```

## Configuration Reference

Key configuration paths in `config/service-desk.php`:

- `models.user` — User model class (default: `App\Models\User`)
- `models.operator` — Operator model class (default: `App\Models\User`)
- `ticket.reference_prefix` — Ticket reference prefix (default: `SD`)
- `ticket.auto_close_days` — Days of inactivity before auto-close (null = disabled)
- `sla.enabled` — Enable SLA module (default: true)
- `sla.auto_apply` — Auto-apply matching SLA to new tickets (default: true)
- `sla.pause_on_statuses` — Statuses that pause SLA timer (default: `['on_hold']`)
- `knowledge_base.enabled` — Enable KB module (default: true)
- `knowledge_base.versioning_enabled` — Track article versions (default: true)
- `service_catalog.enabled` — Enable Service Catalog (default: true)
- `service_catalog.auto_create_ticket` — Auto-create ticket from service request (default: true)
- `email.enabled` — Enable email integration (default: true)
- `email.threading_enabled` — Email thread resolution (default: true)
- `register_default_listeners` — Auto-register event listeners (default: true)
