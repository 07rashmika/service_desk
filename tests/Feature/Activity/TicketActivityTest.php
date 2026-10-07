<?php

namespace Tests\Feature\Activity;

use App\Enums\TicketState;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketActivityTest extends TestCase
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

    public function test_taking_and_working_a_ticket_is_recorded_with_who_did_it(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->technician)->post(route('support.tickets.take', $ticket));
        $this->actingAs($this->technician)->post(route('support.tickets.start', $ticket));

        $entries = ActivityLog::query()->forSubject($ticket)->orderBy('id')->get();

        $this->assertSame(['ticket.assigned', 'ticket.status_changed', 'ticket.status_changed'], $entries->pluck('event')->all());
        $this->assertTrue($entries->every(fn (ActivityLog $entry): bool => $entry->user_id === $this->technician->id));
        $this->assertSame(['status' => 'Assigned'], $entries->last()->properties['old']);
        $this->assertSame(['status' => 'In Progress'], $entries->last()->properties['new']);
    }

    public function test_priority_changes_are_recorded_with_before_and_after(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create();
        $critical = TicketPriority::factory()->create(['name' => 'Critical']);
        $oldPriority = $ticket->priority->name;

        $this->actingAs($this->technician)->patch(route('support.tickets.triage', $ticket), ['category_id' => $ticket->category_id, 'priority_id' => $critical->id]);

        $entry = ActivityLog::query()->forSubject($ticket)->where('event', 'ticket.triaged')->sole();
        $this->assertSame(['priority' => ['old' => $oldPriority, 'new' => 'Critical']], $entry->changes());
    }

    public function test_the_ticket_page_shows_the_history(): void
    {
        $ticket = Ticket::factory()->for($this->employee(), 'creator')->create();
        $this->actingAs($this->technician)->post(route('support.tickets.take', $ticket));

        $this->actingAs($ticket->creator)
            ->get(route('tickets.show', $ticket))
            ->assertSee("{$this->technician->name} took the ticket")
            ->assertSee('changed the status from Open to Assigned');
    }

    public function test_requesters_do_not_see_sla_breaches_in_their_history(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create(['due_at' => now()->subMinute()]);
        $this->artisan('tickets:check-sla');

        $this->actingAs($ticket->creator)->get(route('tickets.show', $ticket))->assertDontSee('Missed the');
        $this->actingAs($this->technician)->get(route('support.tickets.show', $ticket))->assertSee('Missed the');
    }
}
