<?php

namespace Tests\Feature\Activity;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        Notification::fake();
        $this->admin = $this->admin();
    }

    public function test_changing_a_users_role_is_recorded_with_before_and_after(): void
    {
        $user = User::factory()->withRole(RoleName::Employee)->create();

        $this->actingAs($this->admin)->put(route('admin.users.update', $user), ['name' => $user->name, 'email' => $user->email, 'role' => 'support']);

        $entry = ActivityLog::query()->forSubject($user)->where('event', 'user.updated')->sole();
        $this->assertTrue($entry->user->is($this->admin));
        $this->assertSame(['role' => ['old' => 'Employee', 'new' => 'IT Support']], $entry->changes());
    }

    public function test_permission_changes_record_what_was_added_and_removed(): void
    {
        $keep = collect(RoleName::Support->defaultPermissions())
            ->reject(fn (PermissionName $permission): bool => $permission === PermissionName::ViewReports)
            ->map->value->push(PermissionName::ExportReports->value)->values()->all();

        $this->actingAs($this->admin)->put(route('admin.roles.update', 'support'), ['permissions' => $keep]);

        $entry = ActivityLog::query()->where('event', 'role.permissions_changed')->sole();
        $this->assertSame(['removed' => ['old' => [PermissionName::ViewReports->value], 'new' => null], 'added' => ['old' => null, 'new' => [PermissionName::ExportReports->value]]], $entry->changes());
    }

    public function test_the_activity_page_lists_and_filters_entries(): void
    {
        $ticket = Ticket::factory()->create();
        $this->actingAs($this->supportAgent())->post(route('support.tickets.take', $ticket));
        $this->actingAs($this->admin)->post(route('admin.departments.store'), ['name' => 'Legal']);

        $this->actingAs($this->admin)
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('took the ticket')
            ->assertSee('added the Legal department')
            ->assertSee($ticket->reference);

        $this->actingAs($this->admin)
            ->get(route('admin.activity.index', ['type' => 'settings.']))
            ->assertSee('added the Legal department')
            ->assertDontSee('took the ticket');
    }

    public function test_only_people_with_the_activity_permission_can_see_the_log(): void
    {
        $this->actingAs($this->supportAgent())->get(route('admin.activity.index'))->assertForbidden();
    }
}
