<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class DepartmentsTest extends TestCase
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

    public function test_departments_can_be_added_renamed_and_listed(): void
    {
        $this->actingAs($this->admin)->post(route('admin.departments.store'), ['name' => 'Legal'])->assertSessionHas('success');
        $legal = Department::where('name', 'Legal')->sole();

        $this->actingAs($this->admin)->put(route('admin.departments.update', $legal), ['name' => 'Legal & Compliance']);

        $this->actingAs($this->admin)->get(route('admin.departments.index'))->assertSee('Legal &amp; Compliance', false);
    }

    public function test_names_must_be_unique(): void
    {
        Department::factory()->create(['name' => 'Finance']);

        $this->actingAs($this->admin)->post(route('admin.departments.store'), ['name' => 'Finance'])
            ->assertSessionHasErrorsIn('createDepartment', 'name');
    }

    public function test_only_empty_departments_can_be_deleted(): void
    {
        $empty = Department::factory()->create();
        $busy = Department::factory()->has(User::factory())->create();

        $this->actingAs($this->admin)->delete(route('admin.departments.destroy', $empty))->assertSessionHas('success');
        $this->actingAs($this->admin)->delete(route('admin.departments.destroy', $busy))->assertSessionHas('error');

        $this->assertModelMissing($empty);
        $this->assertModelExists($busy);
    }
}
