<?php

namespace JeffersonGoncalves\ServiceDesk\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;
use JeffersonGoncalves\ServiceDesk\Events\ServiceRequestCreated;
use JeffersonGoncalves\ServiceDesk\Events\ServiceRequestStatusChanged;
use JeffersonGoncalves\ServiceDesk\Models\Service;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequest;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

class ServiceRequestService
{
    public function create(Service $service, Model $requester, array $formData, ?string $notes = null): ServiceRequest
    {
        return DB::transaction(function () use ($service, $requester, $formData, $notes) {
            $serviceRequest = new ServiceRequest;
            $serviceRequest->service_id = $service->id;
            $serviceRequest->requester_type = $requester->getMorphClass();
            $serviceRequest->requester_id = $requester->getKey();
            $serviceRequest->form_data = $formData;
            $serviceRequest->notes = $notes;
            $serviceRequest->status = ServiceRequestStatus::Pending;
            $serviceRequest->save();

            if (config('service-desk.service_catalog.auto_create_ticket', true)) {
                $ticketService = app(TicketService::class);
                $ticket = $ticketService->create([
                    'title' => $service->name,
                    'description' => $notes ?? $service->description ?? $service->name,
                    'priority' => $service->default_priority,
                    'department_id' => $service->department_id,
                ], $requester);

                $serviceRequest->ticket_id = $ticket->id;
                $serviceRequest->save();
            }

            event(new ServiceRequestCreated($serviceRequest));

            return $serviceRequest;
        });
    }

    public function updateStatus(ServiceRequest $serviceRequest, ServiceRequestStatus $newStatus): ServiceRequest
    {
        $oldStatus = $serviceRequest->status;

        $serviceRequest->status = $newStatus;
        $serviceRequest->save();

        event(new ServiceRequestStatusChanged($serviceRequest, $oldStatus, $newStatus));

        return $serviceRequest;
    }

    public function linkTicket(ServiceRequest $serviceRequest, Ticket $ticket): ServiceRequest
    {
        $serviceRequest->ticket_id = $ticket->id;
        $serviceRequest->save();

        return $serviceRequest;
    }
}
