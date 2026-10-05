<?php

namespace Database\Seeders;

use App\Enums\Palette;
use App\Models\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    /**
     * Priorities with their SLA targets in hours. Existing rows are left
     * untouched so SLA changes made by an admin survive a re-run.
     */
    public function run(): void
    {
        $priorities = [
            ['slug' => 'low', 'name' => 'Low', 'color' => Palette::Slate, 'level' => 1, 'response_hours' => 8, 'resolution_hours' => 72,
                'description' => 'Minor inconvenience, general question or cosmetic issue.'],
            ['slug' => 'medium', 'name' => 'Medium', 'color' => Palette::Blue, 'level' => 2, 'response_hours' => 4, 'resolution_hours' => 24,
                'description' => 'Affects my work, but I have a workaround.'],
            ['slug' => 'high', 'name' => 'High', 'color' => Palette::Orange, 'level' => 3, 'response_hours' => 1, 'resolution_hours' => 8,
                'description' => "I can't work; it's blocking my daily tasks."],
            ['slug' => 'critical', 'name' => 'Critical', 'color' => Palette::Red, 'level' => 4, 'response_hours' => 1, 'resolution_hours' => 4,
                'description' => 'Affects many people or the business has stopped.'],
        ];

        foreach ($priorities as $priority) {
            TicketPriority::query()->firstOrCreate(['slug' => $priority['slug']], $priority);
        }
    }
}
