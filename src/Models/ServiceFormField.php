<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JeffersonGoncalves\ServiceDesk\Enums\FormFieldType;

/**
 * @property int $id
 * @property int $service_id
 * @property string $name
 * @property string $label
 * @property \JeffersonGoncalves\ServiceDesk\Enums\FormFieldType $type
 * @property bool $is_required
 * @property array|null $options
 * @property array|null $validation_rules
 * @property string|null $placeholder
 * @property string|null $help_text
 * @property string|null $default_value
 * @property int $sort_order
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \JeffersonGoncalves\ServiceDesk\Models\Service $service
 */
class ServiceFormField extends Model
{
    protected $table = 'service_desk_service_form_fields';

    protected $fillable = [
        'service_id',
        'name',
        'label',
        'type',
        'is_required',
        'options',
        'validation_rules',
        'placeholder',
        'help_text',
        'default_value',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'type' => FormFieldType::class,
        'is_required' => 'boolean',
        'options' => 'array',
        'validation_rules' => 'array',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
