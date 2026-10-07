<?php

namespace Tests\Feature\Support;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class SupportWorkflowTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->technician = $this->supportAgent();
    }

    protected function myTicket(TicketState $state): Ticket
    {
        return Ticket::factory()->inState($state, $this->technician)->create(['resolved_at' => null, 'solution' => null, 'first_response_at' => null]);
    }

    public function test_the_assigned_technician_can_start_work(): void
    {
        $ticket = $this->myTicket(TicketState::Assigned);

        $this->actingAs($this->technician)->post(route('support.tickets.start', $ticket))->assertRedirect();

        $ticket->refresh();
        $this->assertSame(TicketState::InProgress, $ticket->state());
        $this->assertNotNull($ticket->first_response_at);
    }

    public function test_only_the_assigned_technician_can_change_the_status(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Assigned, $this->supportAgent())->create();

        $this->actingAs($this->technician)->post(route('support.tickets.start', $ticket))->assertForbidden();

        $this->assertSame(TicketState::Assigned, $ticket->fresh()->state());
    }

    public function test_asking_the_requester_sends_the_question_and_waits_for_a_reply(): void
    {
        $ticket = $this->myTicket(TicketState::InProgress);

        $this->actingAs($this->technician)
            ->post(route('support.tickets.ask', $ticket), ['question' => 'Could you send a screenshot of the error?'])
            ->assertRedirect(route('support.tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame(TicketState::WaitingForUser, $ticket->state());
        $comment = $ticket->comments()->sole();
        $this->assertSame('Could you send a screenshot of the error?', $comment->body);
        $this->assertFalse($comment->is_internal);
    }

    public function test_asking_needs_a_question(): void
    {
        $ticket = $this->myTicket(TicketState::InProgress);

        $this->actingAs($this->technician)
            ->post(route('support.tickets.ask', $ticket), ['question' => ''])
            ->assertSessionHasErrorsIn('askRequester', 'question');

        $this->assertSame(TicketState::InProgress, $ticket->fresh()->state());
    }

    public function test_resolving_saves_the_solution_for_the_requester(): void
    {
        $ticket = $this->myTicket(TicketState::InProgress);

        $this->actingAs($this->technician)
            ->post(route('support.tickets.resolve', $ticket), ['solution' => 'Replaced the faulty dock and tested both monitors.'])
            ->assertRedirect(route('support.tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame(TicketState::Resolved, $ticket->state());
        $this->assertSame('Replaced the faulty dock and tested both monitors.', $ticket->solution);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_resolving_needs_a_meaningful_solution(): void
    {
        $ticket = $this->myTicket(TicketState::InProgress);

        $this->actingAs($this->technician)
            ->post(route('support.tickets.resolve', $ticket), ['solution' => 'Fixed'])
            ->assertSessionHasErrorsIn('resolveTicket', 'solution');

        $this->assertSame(TicketState::InProgress, $ticket->fresh()->state());
    }

    public function test_a_ticket_must_be_started_before_it_can_be_resolved(): void
    {
        $ticket = $this->myTicket(TicketState::Assigned);

        $this->actingAs($this->technician)
            ->post(route('support.tickets.resolve', $ticket), ['solution' => 'Replaced the faulty dock.'])
            ->assertForbidden();
    }

    public function test_staff_cannot_close_tickets_for_the_requester(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Resolved, $this->technician)->create();

        $this->actingAs($this->technician)->post(route('tickets.close', $ticket))->assertForbidden();
    }

    public function test_changing_the_priority_moves_the_sla_deadline(): void
    {
        $this->freezeSecond();
        $critical = TicketPriority::factory()->create(['resolution_hours' => 4]);
        $category = TicketCategory::factory()->create();
        $ticket = $this->myTicket(TicketState::InProgress);

        $this->actingAs($this->technician)
            ->patch(route('support.tickets.triage', $ticket), ['category_id' => $category->id, 'priority_id' => $critical->id])
            ->assertRedirect(route('support.tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame($critical->id, $ticket->priority_id);
        $this->assertSame($category->id, $ticket->category_id);
        $this->assertTrue($ticket->due_at->equalTo($ticket->created_at->copy()->addHours(4)));
    }

    public function test_staff_can_add_internal_notes_the_requester_never_sees(): void
    {
        $ticket = $this->myTicket(TicketState::InProgress);

        $this->actingAs($this->technician)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Device is under warranty.', 'internal' => '1'])
            ->assertRedirect(route('support.tickets.show', $ticket).'#comment-'.$ticket->comments()->value('id'));

        $this->assertTrue($ticket->comments()->sole()->is_internal);

        $this->actingAs($ticket->creator)
            ->get(route('tickets.show', $ticket))
            ->assertDontSee('Device is under warranty.');
    }

    public function test_employees_cannot_add_internal_notes(): void
    {
        $employee = $this->employee();
        $ticket = Ticket::factory()->for($employee, 'creator')->inState(TicketState::InProgress)->create();

        $this->actingAs($employee)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Sneaky note', 'internal' => '1'])
            ->assertForbidden();
    }
}
