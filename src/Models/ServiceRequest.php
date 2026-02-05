<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Enums\ServiceRequestStatus;

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
