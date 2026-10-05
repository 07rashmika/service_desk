<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_employees_can_report_issues_but_not_see_other_peoples_tickets(): void
    {
        $employee = User::factory()->withRole(RoleName::Employee)->create();

        $this->assertTrue($employee->can(PermissionName::CreateTickets));
        $this->assertTrue($employee->can(PermissionName::AddComments));
        $this->assertFalse($employee->can(PermissionName::ViewAllTickets));
        $this->assertFalse($employee->can(PermissionName::AddInternalNotes));
    }

    public function test_support_staff_work_tickets_but_cannot_manage_users_or_settings(): void
    {
        $support = User::factory()->withRole(RoleName::Support)->create();

        $this->assertTrue($support->can(PermissionName::ViewAllTickets));
        $this->assertTrue($support->can(PermissionName::ResolveTickets));
        $this->assertTrue($support->can(PermissionName::AddInternalNotes));
        $this->assertFalse($support->can(PermissionName::ManageUsers));
        $this->assertFalse($support->can(PermissionName::ManageSettings));
    }

    public function test_admins_have_every_permission(): void
    {
        $admin = User::factory()->withRole(RoleName::Admin)->create();

        foreach (PermissionName::cases() as $permission) {
            $this->assertTrue($admin->can($permission), "Admin is missing {$permission->value}");
        }
    }

    public function test_reseeding_keeps_permission_changes_made_by_an_admin(): void
    {
        Role::findByName(RoleName::Support->value)->revokePermissionTo(PermissionName::ViewReports->value);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertFalse(Role::findByName(RoleName::Support->value)->hasPermissionTo(PermissionName::ViewReports->value));
    }

    public function test_primary_role_prefers_the_most_privileged_role(): void
    {
        $user = User::factory()->withRole(RoleName::Employee)->withRole(RoleName::Admin)->create();

        $this->assertSame(RoleName::Admin, $user->primaryRole());
        $this->assertNull(User::factory()->create()->primaryRole());
    }
}
