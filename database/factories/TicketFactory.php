<?php

namespace JeffersonGoncalves\ServiceDesk\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Enums\TicketPriority;
use JeffersonGoncalves\ServiceDesk\Enums\TicketSource;
use JeffersonGoncalves\ServiceDesk\Enums\TicketStatus;
use JeffersonGoncalves\ServiceDesk\Models\Department;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $user = $this->resolveUser();

        return [
            'department_id' => Department::factory(),
            'category_id' => null,
            'user_type' => $user->getMorphClass(),
            'user_id' => $user->getKey(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Medium,
            'source' => TicketSource::Web->value,
        ];
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function priority(TicketPriority $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }

    public function assignedTo(Model $operator): static
    {
        return $this->state(fn () => [
            'assigned_to_type' => $operator->getMorphClass(),
            'assigned_to_id' => $operator->getKey(),
        ]);
    }

    protected function resolveUser(): Model
    {
        /** @var class-string<Model> $userModel */
        $userModel = config('service-desk.models.user', User::class);

        return $userModel::query()->first() ?? $userModel::query()->create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ]);
    }
}
