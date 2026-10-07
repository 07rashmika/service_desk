<?php

namespace Tests\Feature\Admin;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class CategoriesTest extends TestCase
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

    public function test_categories_can_be_added_and_edited(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), ['name' => 'Mobile Phones', 'description' => 'Work phones and SIMs', 'icon' => 'phone_iphone', 'sort_order' => 3])
            ->assertSessionHas('success');

        $category = TicketCategory::where('name', 'Mobile Phones')->sole();
        $this->assertTrue($category->is_active);

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), ['name' => 'Phones & Tablets', 'icon' => 'phone_iphone', 'sort_order' => 1]);

        $this->assertSame('Phones & Tablets', $category->fresh()->name);
    }

    public function test_only_known_icons_are_accepted(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), ['name' => 'Odd', 'icon' => '<script>', 'sort_order' => 1])
            ->assertSessionHasErrorsIn('createCategory', 'icon');
    }

    public function test_switched_off_categories_disappear_from_the_report_an_issue_form(): void
    {
        $category = TicketCategory::factory()->create(['name' => 'Fax machines']);

        $this->actingAs($this->admin)->post(route('admin.categories.toggle', $category));
        $this->assertFalse($category->fresh()->is_active);

        $offered = $this->actingAs($this->employee())->get(route('tickets.create'))->viewData('categories');
        $this->assertFalse($offered->contains($category));
    }

    public function test_categories_used_by_tickets_cannot_be_deleted(): void
    {
        $used = TicketCategory::factory()->create();
        Ticket::factory()->for($used, 'category')->create();
        $unused = TicketCategory::factory()->create();

        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $used))->assertSessionHas('error');
        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $unused))->assertSessionHas('success');

        $this->assertModelExists($used);
        $this->assertModelMissing($unused);
    }
}
