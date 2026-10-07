<?php

namespace Tests\Feature\Support;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class SupportDashboardTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $this->technician = $this->supportAgent();
    }

    public function test_support_staff_get_the_technician_dashboard(): void
    {
        Ticket::factory()->count(2)->create();
        Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create(['title' => 'Fix the boardroom screen', 'due_at' => now()->subHour()]);
        Ticket::factory()->inState(TicketState::Resolved, $this->technician)->create(['resolved_at' => now()]);

        $response = $this->actingAs($this->technician)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Technician view')
            ->assertSee('Fix the boardroom screen')
            ->assertSee('Unassigned queue');

        $stats = $response->viewData('stats');
        $this->assertSame(2, $stats['unassigned']);
        $this->assertSame(1, $stats['mine']);
        $this->assertSame(1, $stats['overdue']);
        $this->assertSame(1, collect($response->viewData('resolvedPerDay'))->last()['count']);
    }

    public function test_employees_still_get_their_own_dashboard(): void
    {
        $this->actingAs($this->employee())
            ->get(route('dashboard'))
            ->assertDontSee('Technician view')
            ->assertSee('Recent tickets');
    }
}
