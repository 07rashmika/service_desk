<?php

namespace Tests\Feature\Support;

use App\Enums\RoleName;
use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class SupportTicketPageTest extends TestCase
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

    public function test_unassigned_tickets_offer_the_take_button(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->technician)
            ->get(route('support.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Take ticket')
            ->assertDontSee('Mark as resolved');
    }

    public function test_the_assigned_technician_sees_the_next_workflow_steps(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create(['resolved_at' => null]);

        $this->actingAs($this->technician)
            ->get(route('support.tickets.show', $ticket))
            ->assertSee('Ask requester')
            ->assertSee('Mark as resolved')
            ->assertDontSee('Take ticket')
            ->assertDontSee('Start progress');
    }

    public function test_other_technicians_see_who_is_working_on_the_ticket(): void
    {
        $colleague = User::factory()->withRole(RoleName::Support)->create(['name' => 'Elena Rostova']);
        $ticket = Ticket::factory()->inState(TicketState::InProgress, $colleague)->create();

        $this->actingAs($this->technician)
            ->get(route('support.tickets.show', $ticket))
            ->assertSee('Elena Rostova is working on this ticket.')
            ->assertDontSee('Mark as resolved');
    }

    public function test_the_page_shows_the_requesters_contact_details(): void
    {
        $requester = $this->employee();
        $requester->update(['phone' => '+94 77 123 4567']);
        $ticket = Ticket::factory()->for($requester, 'creator')->create();

        $this->actingAs($this->technician)
            ->get(route('support.tickets.show', $ticket))
            ->assertSee($requester->email)
            ->assertSee('+94 77 123 4567');
    }

    public function test_employees_cannot_open_the_support_view(): void
    {
        $employee = $this->employee();
        $ticket = Ticket::factory()->for($employee, 'creator')->create();

        $this->actingAs($employee)
            ->get(route('support.tickets.show', $ticket))
            ->assertForbidden();
    }
}
