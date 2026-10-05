<?php

namespace Database\Factories;

use App\Enums\Palette;
use App\Models\TicketPriority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketPriority>
 */
class TicketPriorityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'color' => fake()->randomElement(Palette::cases()),
            'level' => fake()->numberBetween(1, 4),
            'response_hours' => fake()->numberBetween(1, 8),
            'resolution_hours' => fake()->numberBetween(8, 72),
        ];
    }
}
