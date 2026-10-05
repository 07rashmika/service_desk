<?php

namespace Database\Factories;

use App\Enums\Palette;
use App\Enums\TicketState;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatus>
 */
class TicketStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return $this->attributesFor(fake()->unique()->randomElement(TicketState::cases()));
    }

    /**
     * Create the status row for a specific workflow state.
     */
    public function forState(TicketState $state): static
    {
        return $this->state(fn (array $attributes) => $this->attributesFor($state));
    }

    /**
     * @return array{name: string, slug: TicketState, color: Palette, sort_order: int, is_final: bool}
     */
    protected function attributesFor(TicketState $state): array
    {
        return [
            'name' => $state->label(),
            'slug' => $state,
            'color' => $state->defaultColor(),
            'sort_order' => $state->sortOrder(),
            'is_final' => $state->isFinal(),
        ];
    }
}
