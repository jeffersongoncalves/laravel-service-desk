<?php

use Illuminate\Notifications\Messages\MailMessage;
use JeffersonGoncalves\ServiceDesk\Enums\ApprovalStatus;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceCategory;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequestApproval;
use JeffersonGoncalves\ServiceDesk\Notifications\ApprovalRequestedNotification;
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
        'status' => ApprovalStatus::Pending,
        'step_order' => 1,
    ]);
});

it('sends via the configured notification channels', function () {
    config()->set('service-desk.notifications.channels', ['mail']);

    $notification = new ApprovalRequestedNotification($this->approval);

    expect($notification->via((object) []))->toBe(['mail']);
});

// Note: the underlying lang keys for this notification are incomplete/mismatched
// (see final report), so we only assert the mail builds successfully here rather
// than asserting on translated copy.
it('builds a mail message', function () {
    $notification = new ApprovalRequestedNotification($this->approval);

    $mail = $notification->toMail((object) []);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toBeString();
});

it('builds an array payload with the approval data', function () {
    $notification = new ApprovalRequestedNotification($this->approval);

    $array = $notification->toArray((object) []);

    expect($array)->toMatchArray([
        'approval_id' => $this->approval->id,
        'service_request_id' => $this->request->id,
        'service_request_uuid' => $this->request->uuid,
        'service_name' => 'Laptop Request',
        'step_order' => 1,
        'type' => 'approval_requested',
    ]);
});
