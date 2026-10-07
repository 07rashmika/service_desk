<?php

namespace Tests\Feature\Admin;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class ManageUsersTest extends TestCase
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

    public function test_the_users_page_lists_and_filters_people(): void
    {
        User::factory()->withRole(RoleName::Support)->create(['name' => 'Kasun Perera']);
        User::factory()->withRole(RoleName::Employee)->create(['name' => 'Nimal Perera']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['role' => 'support']))
            ->assertOk()
            ->assertSee('Kasun Perera')
            ->assertDontSee('Nimal Perera');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['search' => 'nimal']))
            ->assertSee('Nimal Perera')
            ->assertDontSee('Kasun Perera');
    }

    public function test_admins_can_create_an_account_and_the_person_is_emailed_a_link_to_set_their_password(): void
    {
        Notification::fake();
        $department = Department::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Amaya Silva',
                'email' => 'amaya@example.com',
                'job_title' => 'Accountant',
                'department_id' => $department->id,
                'role' => 'employee',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user = User::where('email', 'amaya@example.com')->sole();
        $this->assertSame(RoleName::Employee, $user->primaryRole());
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->department->is($department));

        Notification::assertSentTo($user, AccountCreated::class, function (AccountCreated $notification) use ($user): bool {
            $this->assertTrue(Password::broker()->tokenExists($user, $notification->token));

            return true;
        });
    }

    public function test_the_new_person_can_set_their_password_from_the_email(): void
    {
        Notification::fake();
        $this->actingAs($this->admin)->post(route('admin.users.store'), ['name' => 'Amaya Silva', 'email' => 'amaya@example.com', 'role' => 'employee']);
        $user = User::where('email', 'amaya@example.com')->sole();
        auth()->logout();

        Notification::assertSentTo($user, AccountCreated::class, function (AccountCreated $notification) use ($user): bool {
            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_emails_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), ['name' => 'Someone', 'email' => 'taken@example.com', 'role' => 'employee'])
            ->assertSessionHasErrorsIn('createUser', 'email');
    }

    public function test_admins_can_change_someones_details_and_role(): void
    {
        $user = User::factory()->withRole(RoleName::Employee)->create();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), ['name' => 'Kasun Perera', 'email' => $user->email, 'role' => 'support', 'job_title' => 'Technician'])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Kasun Perera', $user->name);
        $this->assertSame('Technician', $user->job_title);
        $this->assertSame([RoleName::Support->value], $user->getRoleNames()->all());
    }

    public function test_saving_from_a_filtered_list_returns_to_the_same_filters(): void
    {
        $user = User::factory()->withRole(RoleName::Employee)->create();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', [$user, 'role' => 'employee', 'page' => 2]), ['name' => $user->name, 'email' => $user->email, 'role' => 'support'])
            ->assertRedirect(route('admin.users.index', ['role' => 'employee', 'page' => 2]));
    }

    public function test_admins_cannot_remove_their_own_admin_role(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->admin), ['name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'employee'])
            ->assertSessionHasErrorsIn('editUser', 'role');

        $this->assertSame(RoleName::Admin, $this->admin->fresh()->primaryRole());
    }

    public function test_deactivating_someone_keeps_their_account_but_blocks_sign_in(): void
    {
        $user = User::factory()->withRole(RoleName::Employee)->create();

        $this->actingAs($this->admin)->post(route('admin.users.deactivate', $user))->assertSessionHas('success');
        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($this->admin)->post(route('admin.users.activate', $user))->assertSessionHas('success');
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admins_cannot_deactivate_themselves_or_the_last_admin(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.deactivate', $this->admin))->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_the_only_active_admin_cannot_be_deactivated_by_another_user_manager(): void
    {
        $support = $this->supportAgent();
        $support->givePermissionTo(PermissionName::ManageUsers->value);

        $this->actingAs($support)->post(route('admin.users.deactivate', $this->admin))->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_admins_can_send_a_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->withRole(RoleName::Employee)->create();

        $this->actingAs($this->admin)->post(route('admin.users.password-reset', $user))->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
    }
}
