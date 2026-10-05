<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Reference data (departments, categories, statuses, priorities) is always seeded.
     * Demo users and tickets are only added outside production.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            TicketCategorySeeder::class,
            TicketStatusSeeder::class,
            TicketPrioritySeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
