<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_greets_the_user_by_first_name_with_their_role(): void
    {
        $this->withoutVite();
        $user = User::factory()
            ->withRole(RoleName::Support)
            ->for(Department::factory()->state(['name' => 'IT Operations']))
            ->create(['name' => 'Kasun Perera', 'job_title' => 'IT Support Specialist']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kasun')
            ->assertSee('IT Support')
            ->assertSee('IT Support Specialist · IT Operations');
    }
}
