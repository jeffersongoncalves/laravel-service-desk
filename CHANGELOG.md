# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [v1.0.2] - 2026-02-06

### Added

- Comprehensive README with installation, configuration, usage examples, events, artisan commands, and email integration documentation
- Translated `label()` method to all 12 enums that were missing it: CommentType, HistoryAction, TicketSource, SlaBreachType, EscalationAction, DayOfWeek, ArticleStatus, ArticleVisibility, ServiceCategoryVisibility, ServiceRequestStatus, ApprovalStatus, FormFieldType

### Fixed

- Translation key mismatches in `history_action` (`commented` renamed to `comment_added`)
- Added missing translation keys `category_changed` and `title_changed` in both English and Portuguese

## [1.0.1] - 2026-02-06

### Fixed

- Correct translation keys in TicketPriority and TicketStatus enums

## [1.0.0] - 2026-02-05

### Added

- **Core Module** - Ticket management with full lifecycle (status transitions, priorities, assignments, watchers, auto-generated references)
- **Departments & Categories** - Organization units with operators and hierarchical categories
- **Comments & Notes** - Public replies, internal notes, and system comments with attachment support
- **Tags Module** - Polymorphic tagging system for tickets, articles, and services
- **SLA Management** - Policies with business hours, breach detection, near-breach warnings, pause/resume, and escalation rules
- **Knowledge Base** - Articles with versioning, publishing workflow (Draft/Published/Archived), full-text search, feedback collection, and SEO fields
- **Service Catalog** - Service offerings with dynamic form builder, multi-step approval workflows, and automatic ticket creation
- **Email Integration** - Inbound email processing via IMAP, Mailgun, SendGrid, Resend, and Postmark with thread resolution
- **24 Domain Events** - TicketCreated, TicketStatusChanged, SlaBreached, ArticlePublished, ApprovalRequested, and more
- **10 Notification Classes** - Ticket, SLA, escalation, and approval notifications with queue support
- **6 Artisan Commands** - IMAP polling, SLA breach checking, escalation processing, stale ticket closing, email cleanup, SLA recalculation
- **Translations** - English (en) and Brazilian Portuguese (pt_BR)
- **Webhook Controllers** - Signature-verified endpoints for Mailgun, SendGrid, Resend, and Postmark
- **ServiceDesk Facade** - Clean API for all ticket, comment, department, and attachment operations
- **Traits** - HasTickets, IsOperator, and HasSla for easy model integration
- **Full Test Suite** - 244 tests with Pest framework
- **Static Analysis** - PHPStan/Larastan level configuration
- **Code Style** - Laravel Pint formatting

[Unreleased]: https://github.com/jeffersongoncalves/laravel-service-desk/compare/v1.0.2...HEAD
[v1.0.2]: https://github.com/jeffersongoncalves/laravel-service-desk/compare/1.0.1...v1.0.2
[1.0.1]: https://github.com/jeffersongoncalves/laravel-service-desk/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/jeffersongoncalves/laravel-service-desk/releases/tag/1.0.0
