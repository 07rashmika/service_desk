<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketResolutionTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->employee = $this->employee();
    }

    public function test_requesters_can_confirm_a_resolved_ticket_and_close_it(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::Resolved)->create(['closed_at' => null]);

        $this->actingAs($this->employee)
            ->post(route('tickets.close', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame(TicketState::Closed, $ticket->state());
        $this->assertNotNull($ticket->closed_at);
    }

    public function test_requesters_can_reopen_a_resolved_ticket_with_a_reason(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::Resolved)->create();

        $this->actingAs($this->employee)
            ->post(route('tickets.reopen', $ticket), ['reason' => 'It stopped working again this morning.'])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame(TicketState::InProgress, $ticket->state());
        $this->assertNull($ticket->resolved_at);
        $this->assertSame('It stopped working again this morning.', $ticket->comments()->sole()->body);
    }

    public function test_reopening_needs_a_reason(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::Resolved)->create();

        $this->actingAs($this->employee)
            ->post(route('tickets.reopen', $ticket), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(TicketState::Resolved, $ticket->fresh()->state());
    }

    public function test_only_resolved_tickets_can_be_closed_or_reopened(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::InProgress)->create();

        $this->actingAs($this->employee)->post(route('tickets.close', $ticket))->assertForbidden();
        $this->actingAs($this->employee)->post(route('tickets.reopen', $ticket), ['reason' => 'Still broken'])->assertForbidden();
    }

    public function test_only_the_requester_can_close_or_reopen(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Resolved)->create();

        $this->actingAs($this->employee)->post(route('tickets.close', $ticket))->assertForbidden();
        $this->actingAs($this->supportAgent())->post(route('tickets.close', $ticket))->assertForbidden();

        $this->assertSame(TicketState::Resolved, $ticket->fresh()->state());
    }
}
