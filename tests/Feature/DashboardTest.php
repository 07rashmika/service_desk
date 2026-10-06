<?php

namespace Tests\Feature;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $this->employee = $this->employee();
        $this->employee->update(['name' => 'Nimal Perera']);
    }

    public function test_dashboard_greets_the_user_by_first_name_with_their_role(): void
    {
        $this->actingAs($this->employee)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nimal')
            ->assertSee('Employee')
            ->assertSee('No tickets yet');
    }

    public function test_dashboard_counts_only_the_users_own_tickets(): void
    {
        Ticket::factory()->count(2)->for($this->employee, 'creator')->create();
        Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::InProgress)->create();
        Ticket::factory()->count(3)->create();

        $stats = $this->actingAs($this->employee)->get(route('dashboard'))->viewData('stats');

        $this->assertSame(2, $stats['open']);
        $this->assertSame(1, $stats['inProgress']);
        $this->assertSame(0, $stats['waiting']);
    }

    public function test_tickets_waiting_on_the_user_show_the_technicians_latest_message(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::WaitingForUser)->create([
            'title' => 'VPN drops every 15 minutes',
        ]);
        TicketComment::factory()->for($ticket)->create(['body' => 'Could you send a screenshot of the error?']);

        $this->actingAs($this->employee)
            ->get(route('dashboard'))
            ->assertSee('Needs your attention')
            ->assertSee('VPN drops every 15 minutes')
            ->assertSee('Could you send a screenshot of the error?')
            ->assertSee('Reply now');
    }
}
