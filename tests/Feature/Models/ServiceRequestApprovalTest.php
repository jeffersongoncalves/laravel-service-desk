<?php

use JeffersonGoncalves\ServiceDesk\Enums\ApprovalStatus;
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
    $service = Service::create([
        'category_id' => $category->id,
        'department_id' => $department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'default_priority' => 'medium',
    ]);
    $requester = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);

    $this->request = ServiceRequest::create([
        'service_id' => $service->id,
        'requester_type' => $requester->getMorphClass(),
        'requester_id' => $requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Pending,
    ]);

    $this->approval = ServiceRequestApproval::create([
        'service_request_id' => $this->request->id,
        'approver_type' => config('service-desk.models.operator'),
        'status' => ApprovalStatus::Pending,
        'step_order' => 1,
    ]);
});

// ── approve() / reject() ─────────────────────────────────────────────────────

it('approves the step and records the comment and decision time', function () {
    $this->approval->approve('Looks fine');

    expect($this->approval->status)->toBe(ApprovalStatus::Approved)
        ->and($this->approval->comment)->toBe('Looks fine')
        ->and($this->approval->decided_at)->not->toBeNull();
});

it('rejects the step and records the comment and decision time', function () {
    $this->approval->reject('Not justified');

    expect($this->approval->status)->toBe(ApprovalStatus::Rejected)
        ->and($this->approval->comment)->toBe('Not justified')
        ->and($this->approval->decided_at)->not->toBeNull();
});

// ── isPending() ──────────────────────────────────────────────────────────────

it('reports isPending correctly', function () {
    expect($this->approval->isPending())->toBeTrue();

    $this->approval->approve();

    expect($this->approval->isPending())->toBeFalse();
});

// ── relationships ────────────────────────────────────────────────────────────

it('belongs to a service request', function () {
    expect($this->approval->serviceRequest)->toBeInstanceOf(ServiceRequest::class)
        ->and($this->approval->serviceRequest->id)->toBe($this->request->id);
});
