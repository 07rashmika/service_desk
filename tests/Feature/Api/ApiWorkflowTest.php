<?php

namespace Tests\Feature\Api;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->technician = $this->supportAgent();
    }

    public function test_a_technician_can_work_a_ticket_from_start_to_resolved(): void
    {
        $requester = $this->employee();
        $ticket = Ticket::factory()->for($requester, 'creator')->create();
        Sanctum::actingAs($this->technician);

        $this->postJson(route('api.v1.tickets.take', $ticket))->assertOk()->assertJsonPath('data.status.slug', 'assigned');
        $this->postJson(route('api.v1.tickets.start', $ticket))->assertOk()->assertJsonPath('data.status.slug', 'in_progress');
        $this->postJson(route('api.v1.tickets.ask', $ticket), ['question' => 'Can you send a screenshot?'])
            ->assertOk()
            ->assertJsonPath('data.status.slug', 'waiting_for_user')
            ->assertJsonPath('data.sla.paused', true);
        $this->postJson(route('api.v1.tickets.resolve', $ticket), ['solution' => 'Reinstalled the Wi-Fi driver.'])
            ->assertOk()
            ->assertJsonPath('data.status.slug', 'resolved')
            ->assertJsonPath('data.solution', 'Reinstalled the Wi-Fi driver.');

        Sanctum::actingAs($requester);
        $this->postJson(route('api.v1.tickets.close', $ticket))->assertOk()->assertJsonPath('data.status.slug', 'closed');
    }

    public function test_requesters_can_reopen_a_resolved_ticket_with_a_reason(): void
    {
        $requester = $this->employee();
        $ticket = Ticket::factory()->for($requester, 'creator')->inState(TicketState::Resolved, $this->technician)->create();
        Sanctum::actingAs($requester);

        $this->postJson(route('api.v1.tickets.reopen', $ticket), [])->assertJsonValidationErrors('reason');
        $this->postJson(route('api.v1.tickets.reopen', $ticket), ['reason' => 'Broken again'])
            ->assertOk()
            ->assertJsonPath('data.status.slug', 'in_progress');
    }

    public function test_a_ticket_taken_by_someone_else_cannot_be_taken_again(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Assigned, $this->supportAgent())->create();
        Sanctum::actingAs($this->technician);

        $this->postJson(route('api.v1.tickets.take', $ticket))->assertForbidden();
    }

    public function test_assigning_and_triage(): void
    {
        $colleague = $this->supportAgent();
        $ticket = Ticket::factory()->create();
        $critical = TicketPriority::factory()->create(['name' => 'Critical']);
        Sanctum::actingAs($this->technician);

        $this->postJson(route('api.v1.tickets.assign', $ticket), ['technician_id' => $this->employee()->id])
            ->assertJsonValidationErrors('technician_id');

        $this->postJson(route('api.v1.tickets.assign', $ticket), ['technician_id' => $colleague->id, 'note' => 'Caller on floor 3'])
            ->assertOk()
            ->assertJsonPath('data.assignee.id', $colleague->id);

        $this->patchJson(route('api.v1.tickets.triage', $ticket), ['category_id' => $ticket->category_id, 'priority_id' => $critical->id])
            ->assertOk()
            ->assertJsonPath('data.priority.name', 'Critical');
    }

    public function test_employees_cannot_use_staff_workflow_actions(): void
    {
        $employee = $this->employee();
        $ticket = Ticket::factory()->for($employee, 'creator')->create();
        Sanctum::actingAs($employee);

        $this->postJson(route('api.v1.tickets.take', $ticket))->assertForbidden();
        $this->postJson(route('api.v1.tickets.resolve', $ticket), ['solution' => 'Fixed it myself somehow.'])->assertForbidden();
        $this->patchJson(route('api.v1.tickets.triage', $ticket), ['category_id' => 1, 'priority_id' => 1])->assertForbidden();
    }
}
