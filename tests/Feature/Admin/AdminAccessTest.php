<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function adminPages(): array
    {
        return [
            'users' => ['admin.users.index'],
            'technicians' => ['admin.technicians.index'],
            'departments' => ['admin.departments.index'],
            'categories' => ['admin.categories.index'],
            'priorities' => ['admin.priorities.index'],
            'statuses' => ['admin.statuses.index'],
            'roles' => ['admin.roles.index'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admins_can_open_every_admin_page(string $route): void
    {
        $this->withoutVite();
        $this->setUpTicketReferenceData();

        $this->actingAs($this->admin())->get(route($route))->assertOk();
    }

    #[DataProvider('adminPages')]
    public function test_support_staff_and_employees_cannot(string $route): void
    {
        $this->setUpTicketReferenceData();

        $this->actingAs($this->supportAgent())->get(route($route))->assertForbidden();
        $this->actingAs($this->employee())->get(route($route))->assertForbidden();
    }
}
