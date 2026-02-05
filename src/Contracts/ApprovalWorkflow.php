<?php

namespace JeffersonGoncalves\ServiceDesk\Contracts;

use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequestApproval;

interface ApprovalWorkflow
{
    public function createApprovalSteps(ServiceRequest $request): void;

    public function processDecision(ServiceRequestApproval $approval, string $decision, ?string $comment = null): void;
}
