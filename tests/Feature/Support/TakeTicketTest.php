<?php

namespace Tests\Feature\Support;

use App\Actions\Tickets\TakeTicket;
use App\Enums\TicketState;
use App\Exceptions\TicketAlreadyTaken;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TakeTicketTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->technician = $this->supportAgent();
    }

    public function test_technicians_can_take_an_open_ticket_for_themselves(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.take', $ticket))
            ->assertRedirect(route('support.tickets.show', $ticket))
            ->assertSessionHas('success');

        $ticket->refresh();
        $this->assertTrue($ticket->assignee->is($this->technician));
        $this->assertSame(TicketState::Assigned, $ticket->state());

        $assignment = $ticket->assignments()->sole();
        $this->assertSame($this->technician->id, $assignment->assigned_to);
        $this->assertSame($this->technician->id, $assignment->assigned_by);
    }

    public function test_a_ticket_someone_else_took_cannot_be_taken_again(): void
    {
        $otherTechnician = $this->supportAgent();
        $ticket = Ticket::factory()->inState(TicketState::Assigned, $otherTechnician)->create();

        $this->actingAs($this->technician)
            ->post(route('support.tickets.take', $ticket))
            ->assertSessionHas('error');

        $this->assertTrue($ticket->fresh()->assignee->is($otherTechnician));
    }

    public function test_the_take_action_rejects_a_ticket_taken_a_moment_earlier(): void
    {
        $ticket = Ticket::factory()->create();
        $staleCopy = Ticket::query()->with('status')->find($ticket->id);
        app(TakeTicket::class)->handle($ticket, $this->supportAgent());

        $this->expectException(TicketAlreadyTaken::class);

        app(TakeTicket::class)->handle($staleCopy, $this->technician);
    }

    public function test_employees_cannot_take_tickets(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->employee())
            ->post(route('support.tickets.take', $ticket))
            ->assertForbidden();

        $this->assertNull($ticket->fresh()->assigned_to);
    }
}
