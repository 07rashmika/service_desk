<?php

namespace Tests\Feature\Support;

use App\Enums\RoleName;
use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class AssignTicketTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $technician;

    protected User $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $this->technician = $this->supportAgent();
        $this->colleague = $this->supportAgent();
    }

    public function test_technicians_can_assign_an_open_ticket_to_a_colleague_with_a_note(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.assign', $ticket), ['technician_id' => $this->colleague->id, 'note' => 'Caller is on floor 3.'])
            ->assertRedirect(route('support.tickets.show', $ticket))
            ->assertSessionHas('success', "{$ticket->reference} is now assigned to {$this->colleague->name}.");

        $ticket->refresh();
        $this->assertTrue($ticket->assignee->is($this->colleague));
        $this->assertSame(TicketState::Assigned, $ticket->state());

        $assignment = $ticket->assignments()->sole();
        $this->assertSame($this->technician->id, $assignment->assigned_by);
        $this->assertSame('Caller is on floor 3.', $assignment->note);
    }

    public function test_reassigning_a_ticket_in_progress_keeps_its_status(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.assign', $ticket), ['technician_id' => $this->colleague->id]);

        $ticket->refresh();
        $this->assertTrue($ticket->assignee->is($this->colleague));
        $this->assertSame(TicketState::InProgress, $ticket->state());
    }

    public function test_tickets_can_only_go_to_active_it_staff(): void
    {
        $ticket = Ticket::factory()->create();
        $inactiveTechnician = User::factory()->withRole(RoleName::Support)->inactive()->create();

        foreach ([$this->employee(), $inactiveTechnician] as $notATechnician) {
            $this->actingAs($this->technician)
                ->post(route('support.tickets.assign', $ticket), ['technician_id' => $notATechnician->id])
                ->assertSessionHasErrorsIn('assignTicket', 'technician_id');
        }

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_a_ticket_cannot_be_reassigned_to_the_same_technician(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Assigned, $this->colleague)->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.assign', $ticket), ['technician_id' => $this->colleague->id])
            ->assertSessionHasErrorsIn('assignTicket', 'technician_id');
    }

    public function test_resolved_and_closed_tickets_cannot_be_reassigned(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Resolved, $this->colleague)->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.assign', $ticket), ['technician_id' => $this->technician->id])
            ->assertForbidden();
    }

    public function test_employees_cannot_assign_tickets(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->employee())
            ->post(route('support.tickets.assign', $ticket), ['technician_id' => $this->technician->id])
            ->assertForbidden();
    }

    public function test_the_assign_dialog_lists_technicians_with_their_workload(): void
    {
        Ticket::factory()->count(2)->inState(TicketState::InProgress, $this->colleague)->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->technician)
            ->get(route('support.tickets.show', $ticket))
            ->assertSee('Assign ticket')
            ->assertSee($this->colleague->name)
            ->assertSee('2 open');
    }

    public function test_several_tickets_can_be_assigned_at_once(): void
    {
        $tickets = Ticket::factory()->count(2)->create();
        $closed = Ticket::factory()->inState(TicketState::Closed)->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.bulk'), [
                'action' => 'assign',
                'technician_id' => $this->colleague->id,
                'tickets' => [...$tickets->pluck('id'), $closed->id],
            ])
            ->assertSessionHas('success', "Assigned {$this->colleague->name} to 2 tickets. 1 skipped because it wasn't eligible.");

        $this->assertSame(2, Ticket::where('assigned_to', $this->colleague->id)->count());
    }
}
