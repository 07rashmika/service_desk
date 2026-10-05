<?php

namespace Tests\Feature\Models;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_is_the_padded_id_with_prefix(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertSame('SD-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT), $ticket->reference);
    }

    public function test_reference_keeps_large_ids_intact(): void
    {
        $ticket = new Ticket;
        $ticket->id = 1234567;

        $this->assertSame('SD-1234567', $ticket->reference);
    }

    public function test_ticket_belongs_to_its_lookups_creator_and_assignee(): void
    {
        $technician = User::factory()->create();
        $ticket = Ticket::factory()->inState(TicketState::Assigned, $technician)->create();

        $this->assertTrue($ticket->creator->is(User::find($ticket->created_by)));
        $this->assertTrue($ticket->assignee->is($technician));
        $this->assertSame(TicketState::Assigned, $ticket->status->slug);
        $this->assertNotNull($ticket->category);
        $this->assertNotNull($ticket->priority);
        $this->assertTrue($technician->assignedTickets->contains($ticket));
        $this->assertTrue($ticket->creator->createdTickets->contains($ticket));
    }

    public function test_ticket_has_comments_and_assignment_history(): void
    {
        $ticket = Ticket::factory()->create();
        TicketComment::factory()->count(2)->for($ticket)->create();
        TicketAssignment::factory()->count(2)->for($ticket)->create();

        $this->assertCount(2, $ticket->comments);
        $this->assertCount(2, $ticket->assignments);
    }

    public function test_new_ticket_is_open_and_unassigned_with_a_due_date_from_its_priority(): void
    {
        $this->freezeSecond();

        $ticket = Ticket::factory()->create();

        $this->assertSame(TicketState::Open, $ticket->status->slug);
        $this->assertNull($ticket->assigned_to);
        $this->assertTrue($ticket->due_at->equalTo(now()->addHours($ticket->priority->resolution_hours)));
    }

    public function test_resolved_state_fills_in_the_workflow_fields(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Resolved)->create();

        $this->assertNotNull($ticket->assigned_to);
        $this->assertNotNull($ticket->first_response_at);
        $this->assertNotNull($ticket->solution);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertNull($ticket->closed_at);
    }

    public function test_tickets_in_different_states_share_status_rows(): void
    {
        Ticket::factory()->count(3)->inState(TicketState::InProgress)->create();

        $this->assertSame(1, Ticket::query()->distinct()->count('status_id'));
    }

    public function test_deleting_a_ticket_is_a_soft_delete(): void
    {
        $ticket = Ticket::factory()->create();

        $ticket->delete();

        $this->assertSoftDeleted($ticket);
    }
}
