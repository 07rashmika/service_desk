<?php

namespace Tests\Feature\Sla;

use App\Actions\Tickets\ResolveTicket;
use App\Actions\Tickets\TransitionTicket;
use App\Enums\TicketState;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class SlaPauseTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->freezeSecond();
    }

    public function test_waiting_for_the_requester_pauses_the_clock_and_resuming_moves_the_deadline(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress)->create(['due_at' => now()->addHours(4)]);
        $originalDeadline = $ticket->due_at->copy();

        app(TransitionTicket::class)->handle($ticket, TicketState::WaitingForUser);
        $this->assertTrue($ticket->fresh()->sla_paused_at->equalTo(now()));

        $this->travel(150)->minutes();
        app(TransitionTicket::class)->handle($ticket, TicketState::InProgress);

        $ticket->refresh();
        $this->assertNull($ticket->sla_paused_at);
        $this->assertTrue($ticket->due_at->equalTo($originalDeadline->addMinutes(150)));
    }

    public function test_resolving_straight_from_waiting_also_counts_the_pause(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress)->create(['due_at' => now()->addHour(), 'resolved_at' => null]);
        app(TransitionTicket::class)->handle($ticket, TicketState::WaitingForUser);

        $this->travel(3)->hours();
        app(ResolveTicket::class)->handle($ticket, 'User confirmed it works after a restart.');

        $ticket->refresh();
        $this->assertTrue($ticket->resolved_at->lte($ticket->due_at), 'Time spent waiting on the requester should not count against the SLA.');
    }

    public function test_moving_the_deadline_later_allows_alerts_to_be_sent_again(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress)->create([
            'due_at' => now()->subMinute(),
            'sla_warning_sent_at' => now(),
            'sla_breach_sent_at' => now(),
        ]);

        $ticket->update(['due_at' => now()->addHours(2)]);

        $this->assertNull($ticket->fresh()->sla_warning_sent_at);
        $this->assertNull($ticket->fresh()->sla_breach_sent_at);
    }
}
