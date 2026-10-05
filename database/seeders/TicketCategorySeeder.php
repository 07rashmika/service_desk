<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * The categories employees choose from when reporting an issue.
     * Existing categories are left untouched so admin edits survive a re-run.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Hardware', 'icon' => 'computer', 'description' => 'Laptops, monitors, docks, keyboards and other equipment.'],
            ['name' => 'Software', 'icon' => 'apps', 'description' => 'Installing, licensing or fixing applications.'],
            ['name' => 'Network', 'icon' => 'wifi', 'description' => 'Wi-Fi, VPN and internet connection problems.'],
            ['name' => 'Email & Accounts', 'icon' => 'manage_accounts', 'description' => 'Email, passwords, MFA and account access.'],
            ['name' => 'Printer', 'icon' => 'print', 'description' => 'Printing, scanning and copier issues.'],
            ['name' => 'Other', 'icon' => 'help', 'description' => 'Anything that does not fit another category.'],
        ];

        foreach ($categories as $index => $category) {
            TicketCategory::query()->firstOrCreate(
                ['name' => $category['name']],
                [...$category, 'sort_order' => $index + 1],
            );
        }
    }
}
