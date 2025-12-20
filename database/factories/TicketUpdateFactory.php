<?php

namespace Database\Factories;

use App\Enums\TicketUpdateType;
use App\Models\Ticket;
use App\Models\TicketUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketUpdate>
 */
class TicketUpdateFactory extends Factory
{
    protected $model = TicketUpdate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'created_by_user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'type' => TicketUpdateType::COMMENT,
        ];
    }

    /**
     * Indicate that the update is an internal note.
     */
    public function internalNote(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketUpdateType::INTERNAL_NOTE,
        ]);
    }

    /**
     * Indicate that the update is a status change.
     */
    public function statusChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TicketUpdateType::STATUS_CHANGE,
        ]);
    }
}
