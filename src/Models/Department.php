<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\ServiceDesk\Concerns\HasSlug;
use JeffersonGoncalves\ServiceDesk\Concerns\UsesServiceDeskConnection;
use JeffersonGoncalves\ServiceDesk\Database\Factories\DepartmentFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $email
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Ticket> $tickets
 * @property-read Collection<int, CannedResponse> $cannedResponses
 * @property-read Collection<int, EmailChannel> $emailChannels
 */
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory, HasSlug, SoftDeletes, UsesServiceDeskConnection;

    protected $table = 'service_desk_departments';

    /** @return Factory<self> */
    protected static function newFactory(): Factory
    {
        return DepartmentFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'email',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'department_id');
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'department_id');
    }

    /** @return HasMany<CannedResponse, $this> */
    public function cannedResponses(): HasMany
    {
        return $this->hasMany(CannedResponse::class, 'department_id');
    }

    /** @return HasMany<EmailChannel, $this> */
    public function emailChannels(): HasMany
    {
        return $this->hasMany(EmailChannel::class, 'department_id');
    }

    /**
     * The department's operators who have not turned off email notifications
     * for it, loaded from their own models.
     *
     * Read from the pivot table on the service desk connection, then each
     * morph type is loaded separately on its own model's connection — a join
     * would break when the two live in different databases. Operator types
     * whose class no longer exists in this app are skipped.
     *
     * @param  list<string>  $exceptKeys  "type:id" pairs to leave out.
     * @return list<Model>
     */
    public function operatorsToNotify(array $exceptKeys = []): array
    {
        $rows = $this->getConnection()
            ->table('service_desk_department_operator')
            ->where('department_id', $this->getKey())
            ->where('email_notifications', true)
            ->get(['operator_type', 'operator_id']);

        $operators = [];

        foreach ($rows->groupBy('operator_type') as $type => $group) {
            /** @var class-string<Model> $class */
            $class = Model::getActualClassNameForMorph((string) $type);

            if (! class_exists($class)) {
                continue;
            }

            foreach ($class::query()->whereKey($group->pluck('operator_id')->all())->get() as $operator) {
                if (! in_array($operator->getMorphClass().':'.$operator->getKey(), $exceptKeys, true)) {
                    $operators[] = $operator;
                }
            }
        }

        return $operators;
    }

    /**
     * Whether the operator turned off email notifications for this department.
     */
    public function operatorOptedOutOfEmail(Model $operator): bool
    {
        return $this->getConnection()
            ->table('service_desk_department_operator')
            ->where('department_id', $this->getKey())
            ->where('operator_type', $operator->getMorphClass())
            ->where('operator_id', $operator->getKey())
            ->where('email_notifications', false)
            ->exists();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
