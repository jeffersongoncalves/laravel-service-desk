<?php

declare(strict_types=1);

namespace JeffersonGoncalves\ServiceDesk\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use JeffersonGoncalves\ServiceDesk\Models\ServiceRequestApproval;

class ApprovalDecisionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ServiceRequestApproval $approval,
    ) {
        $this->queue = config('service-desk.notifications.queue', 'default');
    }

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return config('service-desk.notifications.channels', ['mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approval = $this->approval;
        $serviceRequest = $approval->serviceRequest;
        $service = $serviceRequest->service;
        $status = $approval->status->value;

        return (new MailMessage)
            ->subject(__('service-desk::notifications.approval_decision.subject', [
                'service' => $service->name,
                'status' => $status,
            ]))
            ->greeting(__('service-desk::notifications.approval_decision.greeting'))
            ->line(__('service-desk::notifications.approval_decision.body', [
                'service' => $service->name,
                'status' => $status,
            ]))
            ->when($approval->comment, function (MailMessage $message) use ($approval) {
                $message->line(__('service-desk::notifications.approval_decision.comment', [
                    'comment' => $approval->comment,
                ]));
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $serviceRequest = $this->approval->serviceRequest;

        return [
            'approval_id' => $this->approval->id,
            'service_request_id' => $serviceRequest->id,
            'service_request_uuid' => $serviceRequest->uuid,
            'service_name' => $serviceRequest->service->name,
            'status' => $this->approval->status->value,
            'comment' => $this->approval->comment,
            'type' => 'approval_decision',
        ];
    }
}
