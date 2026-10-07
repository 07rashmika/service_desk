<?php

namespace Tests\Feature\Reports;

use App\Enums\TicketState;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
    }

    public function test_admins_get_the_overview_with_what_needs_attention(): void
    {
        Ticket::factory()->create(['due_at' => now()->subHour()]);
        Ticket::factory()->create(['due_at' => now()->addHour()]);
        Ticket::factory()->inState(TicketState::WaitingForUser)->create();

        $response = $this->actingAs($this->admin())->get(route('dashboard'));

        $response->assertOk()->assertSee('Admin · Last 30 days')->assertSee('Tickets created vs resolved');
        $this->assertSame(['overdue' => 1, 'unassigned' => 2, 'waiting' => 1], $response->viewData('attention'));
    }

    public function test_support_staff_keep_the_technician_dashboard(): void
    {
        $this->actingAs($this->supportAgent())->get(route('dashboard'))->assertSee('Technician view')->assertDontSee('Admin · Last 30 days');
    }
}
