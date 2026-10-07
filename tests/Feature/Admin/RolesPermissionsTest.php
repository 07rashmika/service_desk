<?php

namespace Tests\Feature\Admin;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class RolesPermissionsTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $this->admin = $this->admin();
    }

    public function test_the_grid_shows_the_selected_roles_permissions(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.roles.index', ['role' => 'support']))
            ->assertOk()
            ->assertSee('Assign &amp; reassign tickets', false)
            ->assertViewHas('selected', RoleName::Support);
    }

    public function test_admins_can_change_what_a_role_can_do(): void
    {
        $support = $this->supportAgent();
        $this->assertTrue($support->can(PermissionName::AssignTickets));

        $keep = collect(RoleName::Support->defaultPermissions())
            ->reject(fn (PermissionName $permission): bool => $permission === PermissionName::AssignTickets)
            ->map->value->values()->all();

        $this->actingAs($this->admin)
            ->put(route('admin.roles.update', 'support'), ['permissions' => $keep])
            ->assertRedirect(route('admin.roles.index', ['role' => 'support']));

        $this->assertFalse($support->fresh()->can(PermissionName::AssignTickets));
        $this->assertTrue($support->fresh()->can(PermissionName::ViewAllTickets));
    }

    public function test_the_admin_role_always_keeps_user_and_role_management(): void
    {
        $this->actingAs($this->admin)->put(route('admin.roles.update', 'admin'), ['permissions' => []]);

        $admin = Role::findByName(RoleName::Admin->value);
        $this->assertTrue($admin->hasPermissionTo(PermissionName::ManageUsers->value));
        $this->assertTrue($admin->hasPermissionTo(PermissionName::ManageRoles->value));
        $this->assertFalse($admin->hasPermissionTo(PermissionName::DeleteTickets->value));
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.roles.update', 'support'), ['permissions' => ['launch.rockets']])
            ->assertSessionHasErrors('permissions.0');
    }
}
