<?php

namespace JeffersonGoncalves\ServiceDesk\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\ServiceDesk\Enums\CommentType;
use JeffersonGoncalves\ServiceDesk\Models\Ticket;
use JeffersonGoncalves\ServiceDesk\Models\TicketComment;

/**
 * @extends Factory<TicketComment>
 */
class TicketCommentFactory extends Factory
{
    protected $model = TicketComment::class;

    public function definition(): array
    {
        $author = $this->resolveUser();

        return [
            'ticket_id' => Ticket::factory(),
            'author_type' => $author->getMorphClass(),
            'author_id' => $author->getKey(),
            'body' => fake()->paragraph(),
            'type' => CommentType::Reply,
            'is_internal' => false,
        ];
    }

    public function note(): static
    {
        return $this->state(fn () => [
            'type' => CommentType::Note,
            'is_internal' => true,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn () => ['type' => CommentType::System]);
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
