<?php

namespace Tests\Feature\Support;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketQueueTest extends TestCase
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

    public function test_the_queue_shows_every_active_ticket_by_default(): void
    {
        Ticket::factory()->create(['title' => 'Unassigned printer problem']);
        Ticket::factory()->inState(TicketState::InProgress)->create(['title' => 'Someone is fixing the VPN']);
        Ticket::factory()->inState(TicketState::Closed)->create(['title' => 'Old closed ticket']);

        $this->actingAs($this->technician)
            ->get(route('support.tickets.index'))
            ->assertOk()
            ->assertSee('Unassigned printer problem')
            ->assertSee('Someone is fixing the VPN')
            ->assertDontSee('Old closed ticket');
    }

    public function test_tabs_split_the_queue(): void
    {
        Ticket::factory()->create(['title' => 'Nobody has this one']);
        Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create(['title' => 'Mine in progress']);
        Ticket::factory()->inState(TicketState::Resolved)->create(['title' => 'Already resolved']);

        $this->actingAs($this->technician)->get(route('support.tickets.index', ['tab' => 'unassigned']))
            ->assertSee('Nobody has this one')->assertDontSee('Mine in progress');

        $this->actingAs($this->technician)->get(route('support.tickets.assigned'))
            ->assertSee('Mine in progress')->assertDontSee('Nobody has this one');

        $this->actingAs($this->technician)->get(route('support.tickets.index', ['tab' => 'resolved']))
            ->assertSee('Already resolved')->assertDontSee('Mine in progress');
    }

    public function test_the_overdue_tab_leaves_out_tickets_waiting_on_the_requester(): void
    {
        Ticket::factory()->inState(TicketState::InProgress)->create(['title' => 'Late and still being worked on', 'due_at' => now()->subHour()]);
        Ticket::factory()->inState(TicketState::WaitingForUser)->create(['title' => 'Late but paused', 'due_at' => now()->subHour()]);
        Ticket::factory()->inState(TicketState::InProgress)->create(['title' => 'Still on time', 'due_at' => now()->addHour()]);

        $this->actingAs($this->technician)
            ->get(route('support.tickets.index', ['tab' => 'overdue']))
            ->assertSee('Late and still being worked on')
            ->assertDontSee('Late but paused')
            ->assertDontSee('Still on time');
    }

    public function test_the_queue_can_be_filtered_by_technician_and_priority(): void
    {
        $critical = TicketPriority::factory()->create(['name' => 'Critical']);
        Ticket::factory()->inState(TicketState::Assigned, $this->technician)->for($critical, 'priority')->create(['title' => 'Critical and mine']);
        Ticket::factory()->inState(TicketState::Assigned, $this->technician)->create(['title' => 'Mine but routine']);
        Ticket::factory()->inState(TicketState::Assigned)->for($critical, 'priority')->create(['title' => 'Critical for someone else']);

        $this->actingAs($this->technician)
            ->get(route('support.tickets.index', ['technician' => $this->technician->id, 'priority' => $critical->id]))
            ->assertSee('Critical and mine')
            ->assertDontSee('Mine but routine')
            ->assertDontSee('Critical for someone else');
    }

    public function test_the_queue_is_sorted_by_the_most_urgent_deadline_first(): void
    {
        Ticket::factory()->create(['title' => 'Due tomorrow', 'due_at' => now()->addDay()]);
        Ticket::factory()->create(['title' => 'Due in an hour', 'due_at' => now()->addHour()]);

        $this->actingAs($this->technician)
            ->get(route('support.tickets.index'))
            ->assertSeeInOrder(['Due in an hour', 'Due tomorrow']);
    }

    public function test_the_global_search_finds_tickets_in_any_state(): void
    {
        $closed = Ticket::factory()->inState(TicketState::Closed)->create(['title' => 'Projector bulb replaced last month']);

        $this->actingAs($this->technician)
            ->get(route('support.tickets.index', ['tab' => 'all', 'search' => $closed->reference]))
            ->assertSee('Projector bulb replaced last month');
    }

    public function test_employees_cannot_open_the_queue(): void
    {
        $this->actingAs($this->employee())
            ->get(route('support.tickets.index'))
            ->assertForbidden();
    }
}
