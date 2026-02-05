# Service Desk Development Skill

## When to Use
When developing, extending, or debugging the `jeffersongoncalves/laravel-service-desk` package.

## Key Concepts

### Table Prefix
All database tables use `service_desk_` prefix. Never create tables without this prefix.

### Polymorphic Relationships
Users and operators are polymorphic - the package works with any Eloquent model configured in `config/service-desk.php`.

### Event-Driven
All significant actions dispatch events. Listeners handle history logging and notifications.

### Module Structure
Each module (Core, Tags, SLA, Knowledge Base, Service Catalog) has its own:
- Migrations
- Models
- Services
- Events
- Enums

### Testing
- Uses Orchestra Testbench with SQLite in-memory
- Pest test framework
- Test fixtures in `tests/Fixtures/`
- Test migrations in `tests/database/migrations/`

### Configuration
Feature toggles in `config/service-desk.php`:
- `sla.enabled` - Enable/disable SLA module
- `knowledge_base.enabled` - Enable/disable KB module
- `service_catalog.enabled` - Enable/disable Service Catalog module
- `email.enabled` - Enable/disable email integration
- `register_default_listeners` - Auto-register event listeners
