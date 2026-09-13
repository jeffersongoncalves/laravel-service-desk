<?php

use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\ServiceDesk\Enums\ApprovalStatus;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;
use JeffersonGoncalves\ServiceDesk\Events\ApprovalDecisionMade;
use JeffersonGoncalves\ServiceDesk\Events\ApprovalRequested;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceCategory;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequestApproval;
use JeffersonGoncalves\ServiceDesk\Services\ApprovalService;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $this->service = app(ApprovalService::class);

    $this->approvalEvents = [
        ApprovalRequested::class,
        ApprovalDecisionMade::class,
    ];
});

function makeServiceRequest(bool $requiresApproval = true): ServiceRequest
{
    $department = Department::factory()->create();
    $category = ServiceCategory::create(['name' => 'General', 'slug' => 'general']);

    $service = Service::create([
        'category_id' => $category->id,
        'department_id' => $department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'requires_approval' => $requiresApproval,
        'default_priority' => 'medium',
    ]);

    $requester = User::create(['name' => 'Requester', 'email' => 'requester@example.com']);

    return ServiceRequest::create([
        'service_id' => $service->id,
        'requester_type' => $requester->getMorphClass(),
        'requester_id' => $requester->id,
        'form_data' => [],
        'status' => ServiceRequestStatus::Pending,
    ]);
}

// ── createApprovalSteps() ───────────────────────────────────────────────────

it('creates a pending approval step when the service requires approval', function () {
    Event::fake($this->approvalEvents);

    $request = makeServiceRequest(requiresApproval: true);

    $this->service->createApprovalSteps($request);

    expect($request->approvals)->toHaveCount(1)
        ->and($request->approvals->first()->status)->toBe(ApprovalStatus::Pending)
        ->and($request->approvals->first()->step_order)->toBe(1);

    Event::assertDispatched(ApprovalRequested::class);
});

it('does not create an approval step when the service does not require approval', function () {
    Event::fake($this->approvalEvents);

    $request = makeServiceRequest(requiresApproval: false);

    $this->service->createApprovalSteps($request);

    expect($request->approvals)->toHaveCount(0);

    Event::assertNotDispatched(ApprovalRequested::class);
});

// ── processDecision() ───────────────────────────────────────────────────────

it('approves the request when the single approval step is approved', function () {
    Event::fake($this->approvalEvents);

    $request = makeServiceRequest();
    $this->service->createApprovalSteps($request);
    $approval = $request->approvals->first();

    $this->service->processDecision($approval, 'approved', 'Looks good');

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Approved)
        ->and($approval->fresh()->comment)->toBe('Looks good')
        ->and($approval->fresh()->decided_at)->not->toBeNull()
        ->and($request->fresh()->status)->toBe(ServiceRequestStatus::Approved);

    Event::assertDispatched(ApprovalDecisionMade::class);
});

it('rejects the request when the approval step is rejected', function () {
    Event::fake($this->approvalEvents);

    $request = makeServiceRequest();
    $this->service->createApprovalSteps($request);
    $approval = $request->approvals->first();

    $this->service->processDecision($approval, 'rejected', 'Not needed');

    expect($approval->fresh()->status)->toBe(ApprovalStatus::Rejected)
        ->and($request->fresh()->status)->toBe(ServiceRequestStatus::Rejected);

    Event::assertDispatched(ApprovalDecisionMade::class);
});

it('requests the next approval step when more pending steps remain', function () {
    Event::fake($this->approvalEvents);

    $request = makeServiceRequest();
    $this->service->createApprovalSteps($request);
    $firstApproval = $request->approvals->first();

    $secondApproval = ServiceRequestApproval::create([
        'service_request_id' => $request->id,
        'approver_type' => config('service-desk.models.operator'),
        'approver_id' => null,
        'status' => ApprovalStatus::Pending,
        'step_order' => 2,
    ]);

    $this->service->processDecision($firstApproval, 'approved');

    expect($request->fresh()->status)->toBe(ServiceRequestStatus::Pending);

    Event::assertDispatched(ApprovalRequested::class, function ($event) use ($secondApproval) {
        return $event->approval->id === $secondApproval->id;
    });
});

// ── getNextPendingApproval() ────────────────────────────────────────────────

it('returns the earliest pending approval ordered by step', function () {
    $request = makeServiceRequest();

    ServiceRequestApproval::create([
        'service_request_id' => $request->id,
        'approver_type' => config('service-desk.models.operator'),
        'status' => ApprovalStatus::Approved,
        'step_order' => 1,
    ]);

    $pending = ServiceRequestApproval::create([
        'service_request_id' => $request->id,
        'approver_type' => config('service-desk.models.operator'),
        'status' => ApprovalStatus::Pending,
        'step_order' => 2,
    ]);

    expect($this->service->getNextPendingApproval($request)->id)->toBe($pending->id);
});

it('returns null when there is no pending approval', function () {
    $request = makeServiceRequest();

    ServiceRequestApproval::create([
        'service_request_id' => $request->id,
        'approver_type' => config('service-desk.models.operator'),
        'status' => ApprovalStatus::Approved,
        'step_order' => 1,
    ]);

    expect($this->service->getNextPendingApproval($request))->toBeNull();
});

// ── isFullyApproved() ───────────────────────────────────────────────────────

it('is fully approved when all approval steps are approved', function () {
    $request = makeServiceRequest();

    ServiceRequestApproval::create([
        'service_request_id' => $request->id,
        'approver_type' => config('service-desk.models.operator'),
        'status' => ApprovalStatus::Approved,
        'step_order' => 1,
    ]);

    expect($this->service->isFullyApproved($request))->toBeTrue();
});

it('is not fully approved while a step is pending', function () {
    $request = makeServiceRequest();

    ServiceRequestApproval::create([
        'service_request_id' => $request->id,
        'approver_type' => config('service-desk.models.operator'),
        'status' => ApprovalStatus::Approved,
        'step_order' => 1,
    ]);

    ServiceRequestApproval::create([
        'service_request_id' => $request->id,
        'approver_type' => config('service-desk.models.operator'),
        'status' => ApprovalStatus::Pending,
        'step_order' => 2,
    ]);

    expect($this->service->isFullyApproved($request))->toBeFalse();
});
