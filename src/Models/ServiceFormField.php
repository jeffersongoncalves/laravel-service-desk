<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JeffersonGoncalves\ServiceDesk\Enums\FormFieldType;

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
