<?php

use Illuminate\Notifications\Messages\MailMessage;
use JeffersonGoncalves\ServiceDesk\Enums\ApprovalStatus;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceCategory;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequestApproval;
use JeffersonGoncalves\ServiceDesk\Notifications\ApprovalDecisionNotification;
use JeffersonGoncalves\ServiceDesk\Tests\Fixtures\User;

beforeEach(function () {
    $department = Department::factory()->create();
    $category = ServiceCategory::create(['name' => 'General', 'slug' => 'general']);

    $service = Service::create([
        'category_id' => $category->id,
        'department_id' => $department->id,
        'name' => 'Laptop Request',
        'slug' => 'laptop-request',
        'requires_approval' => true,
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
        'status' => ApprovalStatus::Approved,
        'comment' => 'Looks good',
        'step_order' => 1,
    ]);
});

it('sends via the configured notification channels', function () {
    config()->set('service-desk.notifications.channels', ['mail']);

    $notification = new ApprovalDecisionNotification($this->approval);

    expect($notification->via((object) []))->toBe(['mail']);
});

// Note: the underlying lang keys for this notification are incomplete/mismatched
// (see final report), so we only assert the mail builds successfully here rather
// than asserting on translated copy.
it('builds a mail message and adds an extra line when a comment is present', function () {
    $withComment = new ApprovalDecisionNotification($this->approval);
    $mailWithComment = $withComment->toMail((object) []);

    $this->approval->comment = null;
    $withoutComment = new ApprovalDecisionNotification($this->approval);
    $mailWithoutComment = $withoutComment->toMail((object) []);

    expect($mailWithComment)->toBeInstanceOf(MailMessage::class)
        ->and(count($mailWithComment->introLines))->toBeGreaterThan(count($mailWithoutComment->introLines));
});

it('builds an array payload with the decision data', function () {
    $notification = new ApprovalDecisionNotification($this->approval);

    $array = $notification->toArray((object) []);

    expect($array)->toMatchArray([
        'approval_id' => $this->approval->id,
        'service_request_id' => $this->request->id,
        'service_request_uuid' => $this->request->uuid,
        'service_name' => 'Laptop Request',
        'status' => 'approved',
        'comment' => 'Looks good',
        'type' => 'approval_decision',
    ]);
});
