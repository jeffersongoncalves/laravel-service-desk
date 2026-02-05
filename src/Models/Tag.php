<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'service_desk_tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
    ];

    public function tickets(): MorphToMany
    {
        return $this->morphedByMany(Ticket::class, 'taggable', 'service_desk_taggables');
    }

    public function articles(): MorphToMany
    {
        return $this->morphedByMany(KbArticle::class, 'taggable', 'service_desk_taggables');
    }

    public function services(): MorphToMany
    {
        return $this->morphedByMany(Service::class, 'taggable', 'service_desk_taggables');
    }
}
