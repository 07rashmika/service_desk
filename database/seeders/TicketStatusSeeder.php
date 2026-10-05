<?php

namespace Database\Seeders;

use App\Enums\TicketState;
use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    /**
     * One status row per workflow state. Existing rows are left untouched so
     * names and colours changed by an admin survive a re-run.
     */
    public function run(): void
    {
        foreach (TicketState::cases() as $state) {
            TicketStatus::query()->firstOrCreate(
                ['slug' => $state],
                [
                    'name' => $state->label(),
                    'color' => $state->defaultColor(),
                    'sort_order' => $state->sortOrder(),
                    'is_final' => $state->isFinal(),
                ],
            );
        }
    }
}
