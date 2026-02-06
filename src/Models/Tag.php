<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\Ticket> $tickets
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\KbArticle> $articles
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \JeffersonGoncalves\ServiceDesk\Models\Service> $services
 */
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
