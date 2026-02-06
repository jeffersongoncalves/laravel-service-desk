<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;

/**
 * @property int $id
 * @property string $uuid
 * @property int $service_id
 * @property int|null $ticket_id
 * @property string $requester_type
 * @property int $requester_id
 * @property array $form_data
 * @property \JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus $status
 * @property string|null $notes
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\Service $service
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\Ticket|null $ticket
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $requester
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\ServiceRequestApproval> $approvals
 */
class ServiceRequest extends Model
{
    use SoftDeletes;

    protected $table = 'service_desk_service_requests';

    protected $fillable = [
        'uuid',
        'service_id',
        'ticket_id',
        'requester_type',
        'requester_id',
        'form_data',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'status' => ServiceRequestStatus::class,
        'form_data' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $serviceRequest) {
            if (empty($serviceRequest->uuid)) {
                $serviceRequest->uuid = (string) Str::uuid();
            }
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function requester(): MorphTo
    {
        return $this->morphTo('requester');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ServiceRequestApproval::class, 'service_request_id');
    }

    public function scopeByStatus($query, ServiceRequestStatus $status)
    {
        return $query->where('status', $status);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
