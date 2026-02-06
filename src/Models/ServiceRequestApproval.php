<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use JeffersonGoncalves\ServiceDesk\Enums\ApprovalStatus;

/**
 * @property int $id
 * @property int $service_request_id
 * @property string $approver_type
 * @property int $approver_id
 * @property \JeffersonGoncalves\ServiceDesk\Enums\ApprovalStatus $status
 * @property string|null $comment
 * @property int $step_order
 * @property \Illuminate\Support\Carbon|null $decided_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\ServiceRequest $serviceRequest
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $approver
 */
class ServiceRequestApproval extends Model
{
    protected $table = 'service_desk_service_request_approvals';

    protected $fillable = [
        'service_request_id',
        'approver_type',
        'approver_id',
        'status',
        'comment',
        'step_order',
        'decided_at',
    ];

    protected $casts = [
        'status' => ApprovalStatus::class,
        'decided_at' => 'datetime',
        'step_order' => 'integer',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function approver(): MorphTo
    {
        return $this->morphTo('approver');
    }

    public function approve(?string $comment = null): void
    {
        $this->status = ApprovalStatus::Approved;
        $this->comment = $comment;
        $this->decided_at = now();
        $this->save();
    }

    public function reject(?string $comment = null): void
    {
        $this->status = ApprovalStatus::Rejected;
        $this->comment = $comment;
        $this->decided_at = now();
        $this->save();
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }
}
