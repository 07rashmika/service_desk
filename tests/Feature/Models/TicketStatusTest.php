<?php

namespace Tests\Feature\Models;

use App\Enums\Palette;
use App\Enums\TicketState;
use App\Models\TicketStatus;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_found_by_its_workflow_state(): void
    {
        $this->seed(TicketStatusSeeder::class);

        $status = TicketStatus::for(TicketState::WaitingForUser);

        $this->assertSame('Waiting for User', $status->name);
        $this->assertSame(TicketState::WaitingForUser, $status->slug);
        $this->assertSame(Palette::Orange, $status->color);
    }

    public function test_missing_status_throws(): void
    {
        $this->expectException(ModelNotFoundException::class);

        TicketStatus::for(TicketState::Closed);
    }

    public function test_ordered_scope_follows_the_workflow(): void
    {
        $this->seed(TicketStatusSeeder::class);

        $this->assertSame(
            array_map(fn (TicketState $state): string => $state->value, TicketState::cases()),
            TicketStatus::query()->ordered()->get()->map(fn (TicketStatus $status): string => $status->slug->value)->all(),
        );
    }
}
