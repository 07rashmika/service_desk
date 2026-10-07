<?php

namespace Tests\Feature\Support;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class BulkTicketActionTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->technician = $this->supportAgent();
    }

    public function test_technicians_can_take_several_tickets_at_once_and_ineligible_ones_are_skipped(): void
    {
        $open = Ticket::factory()->count(2)->create();
        $alreadyTaken = Ticket::factory()->inState(TicketState::Assigned)->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.bulk'), ['action' => 'take', 'tickets' => [...$open->pluck('id'), $alreadyTaken->id]])
            ->assertSessionHas('success', 'Took 2 tickets. 1 skipped because it wasn\'t eligible.');

        foreach ($open as $ticket) {
            $this->assertTrue($ticket->fresh()->assignee->is($this->technician));
        }
        $this->assertFalse($alreadyTaken->fresh()->assignee->is($this->technician));
    }

    public function test_technicians_can_change_the_priority_of_several_tickets(): void
    {
        $high = TicketPriority::factory()->create();
        $tickets = Ticket::factory()->count(2)->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.bulk'), ['action' => 'priority', 'priority_id' => $high->id, 'tickets' => $tickets->pluck('id')->all()])
            ->assertSessionHas('success');

        $this->assertSame(2, Ticket::where('priority_id', $high->id)->count());
    }

    public function test_a_selection_and_a_priority_are_required(): void
    {
        $this->actingAs($this->technician)
            ->post(route('support.tickets.bulk'), ['action' => 'priority', 'tickets' => []])
            ->assertSessionHasErrors(['tickets', 'priority_id']);
    }

    public function test_employees_cannot_use_bulk_actions(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->employee())
            ->post(route('support.tickets.bulk'), ['action' => 'take', 'tickets' => [$ticket->id]])
            ->assertForbidden();
    }
}
