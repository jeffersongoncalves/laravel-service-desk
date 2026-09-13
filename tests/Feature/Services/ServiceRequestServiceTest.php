<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Events\ServiceRequestCreated;
use JeffersonGoncalves\ServiceDesk\Events\ServiceRequestStatusChanged;
use JeffersonGoncalves\ServiceDesk\Events\TicketCreated;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceCategory;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Services\ServiceRequestService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->service = app(ServiceRequestService::class);

    $this->requestEvents = [
        ServiceRequestCreated::class,
        ServiceRequestStatusChanged::class,
        TicketCreated::class,
    ];

    $this->department = Department::factory()->create();
    $this->category = ServiceCategory::create(['name' => 'General', 'slug' => 'general']);
    $this->requester = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);
});

// ── create() ─────────────────────────────────────────────────────────────────

it('creates a pending service request', function () {
    Event::fake($this->requestEvents);
    config()->set('service-desk.service_catalog.auto_create_ticket', false);

    $service = Service::create([
        'category_id' => $this->category->id,
        'department_id' => $this->department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'default_priority' => 'medium',
    ]);

    $request = $this->service->create($service, $this->requester, ['reason' => 'New hire'], 'Please expedite');

    expect($request)->toBeInstanceOf(ServiceRequest::class)
        ->and($request->service_id)->toBe($service->id)
        ->and($request->requester_id)->toBe($this->requester->id)
        ->and($request->requester_type)->toBe($this->requester->getMorphClass())
        ->and($request->form_data)->toBe(['reason' => 'New hire'])
        ->and($request->notes)->toBe('Please expedite')
        ->and($request->status)->toBe(ServiceRequestStatus::Pending)
        ->and($request->uuid)->not->toBeNull();

    Event::assertDispatched(ServiceRequestCreated::class);
});

it('auto creates a linked ticket when enabled', function () {
    Event::fake($this->requestEvents);
    config()->set('service-desk.service_catalog.auto_create_ticket', true);

    $service = Service::create([
        'category_id' => $this->category->id,
        'department_id' => $this->department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'default_priority' => 'high',
    ]);

    $request = $this->service->create($service, $this->requester, [], 'Needs a new laptop');

    expect($request->ticket_id)->not->toBeNull();

    $ticket = Ticket::find($request->ticket_id);

    expect($ticket)->toBeInstanceOf(Ticket::class)
        ->and($ticket->title)->toBe('Laptop Request')
        ->and($ticket->description)->toBe('Needs a new laptop')
        ->and($ticket->priority)->toBe(TicketPriority::High)
        ->and($ticket->department_id)->toBe($this->department->id);

    Event::assertDispatched(TicketCreated::class);
});

it('does not create a ticket when auto_create_ticket is disabled', function () {
    Event::fake($this->requestEvents);
    config()->set('service-desk.service_catalog.auto_create_ticket', false);

    $service = Service::create([
        'category_id' => $this->category->id,
        'department_id' => $this->department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'default_priority' => 'medium',
    ]);

    $request = $this->service->create($service, $this->requester, []);

    expect($request->ticket_id)->toBeNull();

    Event::assertNotDispatched(TicketCreated::class);
});

// ── updateStatus() ───────────────────────────────────────────────────────────

it('updates the status and dispatches ServiceRequestStatusChanged', function () {
    Event::fake($this->requestEvents);
    config()->set('service-desk.service_catalog.auto_create_ticket', false);

    $service = Service::create([
        'category_id' => $this->category->id,
        'department_id' => $this->department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'default_priority' => 'medium',
    ]);

    $request = $this->service->create($service, $this->requester, []);

    $updated = $this->service->updateStatus($request, ServiceRequestStatus::Approved);

    expect($updated->status)->toBe(ServiceRequestStatus::Approved);

    Event::assertDispatched(ServiceRequestStatusChanged::class, function ($event) {
        return $event->oldStatus === ServiceRequestStatus::Pending
            && $event->newStatus === ServiceRequestStatus::Approved;
    });
});

// ── linkTicket() ─────────────────────────────────────────────────────────────

it('links an existing ticket to a service request', function () {
    Event::fake($this->requestEvents);
    config()->set('service-desk.service_catalog.auto_create_ticket', false);

    $service = Service::create([
        'category_id' => $this->category->id,
        'department_id' => $this->department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'default_priority' => 'medium',
    ]);

    $request = $this->service->create($service, $this->requester, []);
    $ticket = Ticket::factory()->create();

    $linked = $this->service->linkTicket($request, $ticket);

    expect($linked->ticket_id)->toBe($ticket->id)
        ->and($request->fresh()->ticket_id)->toBe($ticket->id);
});
