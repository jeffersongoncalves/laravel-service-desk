<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use JeffersonGoncalves\ServiceDesk\Contracts\ApprovalWorkflow;
use JeffersonGoncalves\ServiceDesk\Enums\ApprovalStatus;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;
use JeffersonGoncalves\ServiceDesk\Events\ApprovalDecisionMade;
use JeffersonGoncalves\ServiceDesk\Events\ApprovalRequested;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequestApproval;

class ApprovalService implements ApprovalWorkflow
{
    public function createApprovalSteps(ServiceRequest $request): void
    {
        $service = $request->service;

        if (! $service->requires_approval) {
            return;
        }

        $approval = ServiceRequestApproval::create([
            'service_request_id' => $request->id,
            'approver_type' => config('service-desk.models.operator'),
            'approver_id' => null,
            'status' => ApprovalStatus::Pending,
            'step_order' => 1,
        ]);

        event(new ApprovalRequested($approval));
    }

    public function processDecision(ServiceRequestApproval $approval, string $decision, ?string $comment = null): void
    {
        if ($decision === 'approved') {
            $approval->approve($comment);
        } else {
            $approval->reject($comment);
        }

        event(new ApprovalDecisionMade($approval));

        $serviceRequest = $approval->serviceRequest;

        if ($decision === 'rejected') {
            $serviceRequest->status = ServiceRequestStatus::Rejected;
            $serviceRequest->save();

            return;
        }

        if ($this->isFullyApproved($serviceRequest)) {
            $serviceRequest->status = ServiceRequestStatus::Approved;
            $serviceRequest->save();
        } else {
            $nextApproval = $this->getNextPendingApproval($serviceRequest);

            if ($nextApproval) {
                event(new ApprovalRequested($nextApproval));
            }
        }
    }

    public function getNextPendingApproval(ServiceRequest $serviceRequest): ?ServiceRequestApproval
    {
        return $serviceRequest->approvals()
            ->where('status', ApprovalStatus::Pending)
            ->orderBy('step_order')
            ->first();
    }

    public function isFullyApproved(ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->approvals()
            ->where('status', '!=', ApprovalStatus::Approved)
            ->doesntExist();
    }
}
