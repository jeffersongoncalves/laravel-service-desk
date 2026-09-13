<?php

use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceCategory;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequestApproval;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $department = Department::factory()->create();
    $category = ServiceCategory::create(['name' => 'General', 'slug' => 'general']);
    $this->service = Service::create([
        'category_id' => $category->id,
        'department_id' => $department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'default_priority' => 'medium',
    ]);
    $this->requester = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);
});

// ── booted() uuid generation ────────────────────────────────────────────────

it('auto generates a uuid on creation', function () {
    $request = ServiceRequest::create([
        'service_id' => $this->service->id,
        'requester_type' => $this->requester->getMorphClass(),
        'requester_id' => $this->requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Pending,
    ]);

    expect($request->uuid)->not->toBeNull()
        ->and($request->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('does not overwrite a provided uuid', function () {
    $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

    $request = ServiceRequest::create([
        'uuid' => $uuid,
        'service_id' => $this->service->id,
        'requester_type' => $this->requester->getMorphClass(),
        'requester_id' => $this->requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Pending,
    ]);

    expect($request->uuid)->toBe($uuid);
});

// ── casts ────────────────────────────────────────────────────────────────────

it('casts form_data and metadata to arrays', function () {
    $request = ServiceRequest::create([
        'service_id' => $this->service->id,
        'requester_type' => $this->requester->getMorphClass(),
        'requester_id' => $this->requester->id,
        'form_data' => ['reason' => 'New hire'],
        'metadata' => ['source' => 'portal'],
        'status' => ServiceRequestStatus::Pending,
    ]);

    expect($request->fresh()->form_data)->toBe(['reason' => 'New hire'])
        ->and($request->fresh()->metadata)->toBe(['source' => 'portal']);
});

it('casts status to ServiceRequestStatus enum', function () {
    $request = ServiceRequest::create([
        'service_id' => $this->service->id,
        'requester_type' => $this->requester->getMorphClass(),
        'requester_id' => $this->requester->id,
        'form_data' => [],
        'status' => 'approved',
    ]);

    expect($request->status)->toBe(ServiceRequestStatus::Approved);
});

// ── relationships ────────────────────────────────────────────────────────────

it('belongs to a service and requester', function () {
    $request = ServiceRequest::create([
        'service_id' => $this->service->id,
        'requester_type' => $this->requester->getMorphClass(),
        'requester_id' => $this->requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Pending,
    ]);

    expect($request->service->id)->toBe($this->service->id)
        ->and($request->requester->id)->toBe($this->requester->id);
});

it('has many approvals', function () {
    $request = ServiceRequest::create([
        'service_id' => $this->service->id,
        'requester_type' => $this->requester->getMorphClass(),
        'requester_id' => $this->requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Pending,
    ]);

    ServiceRequestApproval::create([
        'service_request_id' => $request->id,
        'approver_type' => config('service-desk.models.operator'),
        'status' => 'pending',
        'step_order' => 1,
    ]);

    expect($request->approvals)->toHaveCount(1);
});

// ── scopes ───────────────────────────────────────────────────────────────────

it('scopes requests by status', function () {
    ServiceRequest::create([
        'service_id' => $this->service->id,
        'requester_type' => $this->requester->getMorphClass(),
        'requester_id' => $this->requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Pending,
    ]);

    ServiceRequest::create([
        'service_id' => $this->service->id,
        'requester_type' => $this->requester->getMorphClass(),
        'requester_id' => $this->requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Approved,
    ]);

    expect(ServiceRequest::byStatus(ServiceRequestStatus::Pending)->count())->toBe(1)
        ->and(ServiceRequest::byStatus(ServiceRequestStatus::Approved)->count())->toBe(1);
});

// ── route key ────────────────────────────────────────────────────────────────

it('uses uuid as route key name', function () {
    expect((new ServiceRequest)->getRouteKeyName())->toBe('uuid');
});
