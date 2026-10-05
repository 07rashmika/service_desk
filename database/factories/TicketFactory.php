<?php

namespace Database\Factories;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(6), '.'),
            'description' => fake()->paragraphs(2, true),
            'category_id' => TicketCategory::factory(),
            'priority_id' => TicketPriority::factory(),
            'status_id' => fn () => $this->statusFor(TicketState::Open)->id,
            'created_by' => User::factory(),
            'assigned_to' => null,
            'due_at' => fn (array $attributes) => now()->addHours(
                TicketPriority::query()->findOrFail($attributes['priority_id'])->resolution_hours,
            ),
        ];
    }

    /**
     * Put the ticket at a step of the workflow, filling in the fields that step implies:
     * a technician from Assigned onwards, a first response from In Progress onwards,
     * and a solution once resolved.
     */
    public function inState(TicketState $state, ?User $technician = null): static
    {
        $order = $state->sortOrder();

        return $this->state(fn (array $attributes) => [
            'status_id' => $this->statusFor($state)->id,
            'assigned_to' => $order >= TicketState::Assigned->sortOrder()
                ? ($technician?->id ?? User::factory())
                : null,
            'first_response_at' => $order >= TicketState::InProgress->sortOrder() ? now() : null,
            'solution' => $order >= TicketState::Resolved->sortOrder() ? fake()->paragraph() : null,
            'resolved_at' => $order >= TicketState::Resolved->sortOrder() ? now() : null,
            'closed_at' => $state === TicketState::Closed ? now() : null,
        ]);
    }

    /**
     * Reuse the status row for a state, creating it when the table is empty (e.g. in tests).
     */
    protected function statusFor(TicketState $state): TicketStatus
    {
        return TicketStatus::query()->where('slug', $state)->first()
            ?? TicketStatus::factory()->forState($state)->create();
    }
}
