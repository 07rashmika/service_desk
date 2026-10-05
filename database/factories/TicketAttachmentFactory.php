<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'ticket_comment_id' => null,
            'user_id' => User::factory(),
            'original_name' => fake()->slug(3).'.png',
            'disk' => 'local',
            'path' => 'attachments/'.Str::uuid().'.png',
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(10_000, 2_000_000),
        ];
    }
}
