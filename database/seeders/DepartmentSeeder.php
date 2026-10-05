<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * The company's departments. Safe to run more than once.
     */
    public function run(): void
    {
        $departments = [
            'IT Operations',
            'Engineering',
            'Product & Design',
            'Finance',
            'Human Resources',
            'Sales & Marketing',
            'Operations',
        ];

        foreach ($departments as $name) {
            Department::query()->firstOrCreate(['name' => $name]);
        }
    }
}
