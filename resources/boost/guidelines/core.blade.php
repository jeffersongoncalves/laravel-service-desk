## Package: jeffersongoncalves/laravel-service-desk

Complete backend Service Desk package for Laravel with Tickets, SLA Management, Knowledge Base, Service Catalog, and Email Integration.

### Architecture

- **Namespace:** `JeffersonGoncalves\ServiceDesk`
- **Table Prefix:** `service_desk_`
- **Config Key:** `service-desk`
- **Facade:** `ServiceDesk`
- **Compatible:** Laravel 11+ / PHP 8.2+

### Modules

1. **Core:** Departments, Categories, Tickets, Comments, Attachments, History, Watchers, Canned Responses, Email Channels, Inbound Emails
2. **Tags:** Polymorphic tagging system for Tickets, Articles, and Services
3. **SLA:** Business Hours Schedules, SLA Policies with priority-based targets, Escalation Rules, automatic breach detection
4. **Knowledge Base:** Categories, Articles with versioning and feedback, Related articles, Ticket linking
5. **Service Catalog:** Service Categories, Services with dynamic form fields, Service Requests with multi-step Approval workflow

### Key Patterns

- All models use `service_desk_` table prefix. Never create tables without this prefix.
- Polymorphic relationships for users and operators — the package works with any Eloquent model configured in `config('service-desk.models.user')` and `config('service-desk.models.operator')`.
- Event-driven architecture: all significant actions dispatch events. Listeners handle history logging and notifications.
- Config-driven feature toggles: `sla.enabled`, `knowledge_base.enabled`, `service_catalog.enabled`, `email.enabled`.
- Uses Spatie Package Tools (`spatie/laravel-package-tools`) for service provider registration.
- User traits: `HasTickets` for end users, `IsOperator` for agents/operators.

### Working with the Facade

The `ServiceDesk` facade delegates to `TicketService`, `CommentService`, `DepartmentService`, and `AttachmentService`:

@verbatim
<code-snippet name="Creating a ticket via Facade" lang="php">
use JeffersonGoncalves\ServiceDesk\Facades\ServiceDesk;

$ticket = ServiceDesk::createTicket([
    'department_id' => 1,
    'title' => 'Cannot login to my account',
    'description' => 'I am getting a 403 error when trying to log in.',
    'priority' => 'high',
    'source' => 'web',
], $user);
</code-snippet>
@endverbatim

@verbatim
<code-snippet name="Adding a comment to a ticket" lang="php">
use JeffersonGoncalves\ServiceDesk\Facades\ServiceDesk;

$comment = ServiceDesk::addComment($ticket, $operator, 'We are investigating the issue.', [
    'type' => 'reply',
    'is_internal' => false,
]);
</code-snippet>
@endverbatim

### Working with Services Directly

@verbatim
<code-snippet name="Using TicketService" lang="php">
use JeffersonGoncalves\ServiceDesk\Services\TicketService;

$ticketService = app(TicketService::class);

// Create
$ticket = $ticketService->create([
    'department_id' => 1,
    'title' => 'Server is down',
    'description' => 'The production server is not responding.',
    'priority' => 'urgent',
], $user);

// Change status
$ticketService->changeStatus($ticket, \JeffersonGoncalves\ServiceDesk\Enums\TicketStatus::InProgress, $operator);

// Assign
$ticketService->assign($ticket, $operator, $assignedBy);

// Close
$ticketService->close($ticket, $operator);
</code-snippet>
@endverbatim

### SLA Management

@verbatim
<code-snippet name="Working with SLA" lang="php">
use JeffersonGoncalves\ServiceDesk\Services\SlaService;

$slaService = app(SlaService::class);

// Apply SLA to a ticket (auto-calculates due dates based on priority targets and business hours)
$slaService->applyPolicy($ticket, $slaPolicy);

// Check for SLA breaches (typically called via scheduler)
$slaService->checkBreaches();

// Pause SLA when ticket is on hold
$ticket->ticketSla->pause();

// Resume SLA when ticket is active again
$ticket->ticketSla->resume();
</code-snippet>
@endverbatim

### Knowledge Base

@verbatim
<code-snippet name="Working with Knowledge Base" lang="php">
use JeffersonGoncalves\ServiceDesk\Services\KnowledgeBaseService;

$kbService = app(KnowledgeBaseService::class);

// Create article
$article = $kbService->createArticle([
    'category_id' => 1,
    'title' => 'How to reset your password',
    'content' => '...',
], $author);

// Publish
$kbService->publishArticle($article);

// Search
$results = $kbService->search('password reset', categoryId: 1, limit: 10);

// Link article to a ticket
$kbService->linkArticleToTicket($article, $ticket);
</code-snippet>
@endverbatim

### Service Catalog

@verbatim
<code-snippet name="Working with Service Requests" lang="php">
use JeffersonGoncalves\ServiceDesk\Services\ServiceRequestService;
use JeffersonGoncalves\ServiceDesk\Services\ApprovalService;

$requestService = app(ServiceRequestService::class);
$approvalService = app(ApprovalService::class);

// Create service request (auto-validates form data against service form fields)
$request = $requestService->create($service, $user, [
    'reason' => 'Need VPN access for remote work',
    'start_date' => '2026-02-10',
]);

// Approve (if approval workflow is enabled)
$approvalService->approve($approval, $approver, 'Approved for 30 days.');
</code-snippet>
@endverbatim

### Email Integration

The package supports inbound email processing via 5 drivers: IMAP, Mailgun, SendGrid, Resend, Postmark.

@verbatim
<code-snippet name="Configuring email channels" lang="php">
use JeffersonGoncalves\ServiceDesk\Models\EmailChannel;

// Email channels are configured in the database
EmailChannel::create([
    'department_id' => 1,
    'name' => 'Support Inbox',
    'driver' => 'postmark',
    'email_address' => 'support@example.com',
    'settings' => ['server_token' => '...'],
    'is_active' => true,
]);
</code-snippet>
@endverbatim

### Enums

All status/priority fields use PHP 8.1 backed enums:

- `TicketStatus`: Open, Pending, InProgress, OnHold, Resolved, Closed
- `TicketPriority`: Low, Medium, High, Urgent
- `CommentType`: Reply, Note, System
- `ArticleStatus`: Draft, Published, Archived
- `ServiceRequestStatus`: Pending, Approved, Rejected, InProgress, Fulfilled, Cancelled
- `ApprovalStatus`: Pending, Approved, Rejected

### Commands

| Command | Description |
|---------|-------------|
| `service-desk:poll-imap` | Poll IMAP mailboxes for new emails |
| `service-desk:clean-emails` | Clean old processed inbound emails |
| `service-desk:close-stale` | Close tickets inactive for N days |
| `service-desk:check-sla` | Check SLA breaches and near-breaches |
| `service-desk:process-escalations` | Process pending escalation actions |
| `service-desk:recalculate-sla` | Recalculate SLA due dates after schedule changes |

### Testing

Uses Orchestra Testbench with SQLite in-memory and Pest framework. Test fixtures in `tests/Fixtures/`, test migrations in `tests/database/migrations/`.
