<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $category_id
 * @property int|null $sla_policy_id
 * @property int|null $department_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $long_description
 * @property string|null $icon
 * @property bool $requires_approval
 * @property string $default_priority
 * @property int|null $expected_duration_minutes
 * @property string $visibility
 * @property bool $is_active
 * @property int $sort_order
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\ServiceCategory $category
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\SlaPolicy|null $slaPolicy
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\ServiceFormField> $formFields
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\ServiceRequest> $requests
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\Tag> $tags
 */
class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_desk_services';

    protected $fillable = [
        'category_id',
        'sla_policy_id',
        'department_id',
        'name',
        'slug',
        'description',
        'long_description',
        'icon',
        'requires_approval',
        'default_priority',
        'expected_duration_minutes',
        'visibility',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(ServiceFormField::class, 'service_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'service_id');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'service_desk_taggables');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
