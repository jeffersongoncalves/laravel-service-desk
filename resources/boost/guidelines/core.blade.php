{{-- Service Desk Package - AI Guidelines --}}
{{-- This file provides context for AI agents working with this package --}}

## Package: jeffersongoncalves/laravel-service-desk

### Overview
Complete Service Desk package for Laravel with Tickets, SLA Management, Knowledge Base, and Service Catalog.

### Architecture
- **Namespace:** `JeffersonGoncalves\ServiceDesk`
- **Table Prefix:** `service_desk_`
- **Config:** `config/service-desk.php`

### Modules
1. **Core:** Departments, Categories, Tickets, Comments, Attachments, History, Watchers, Canned Responses, Email Channels
2. **Tags:** Polymorphic tagging for Tickets, Articles, Services
3. **SLA:** Business Hours, SLA Policies, Targets, Escalation Rules
4. **Knowledge Base:** Categories, Articles with versioning, Feedback, Related articles
5. **Service Catalog:** Service Categories, Services with form fields, Service Requests, Approvals

### Key Patterns
- All models use `service_desk_` table prefix
- Polymorphic relationships for users/operators (supports any Eloquent model)
- Event-driven architecture with listeners for history logging and notifications
- Config-driven feature toggles for SLA, KB, Service Catalog, Email
- Spatie Package Tools for service provider registration
