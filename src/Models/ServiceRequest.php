<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
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
 * @property ServiceRequestStatus $status
 * @property string|null $notes
 * @property array|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Service $service
 * @property-read Ticket|null $ticket
 * @property-read Model|\Eloquent $requester
 * @property-read Collection<int, ServiceRequestApproval> $approvals
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

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function requester(): MorphTo
    {
        return $this->morphTo('requester');
    }

    /** @return HasMany<ServiceRequestApproval, $this> */
    public function approvals(): HasMany
    {
        return $this->hasMany(ServiceRequestApproval::class, 'service_request_id');
    }

    /** @param Builder<static> $query */
    public function scopeByStatus(Builder $query, ServiceRequestStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
