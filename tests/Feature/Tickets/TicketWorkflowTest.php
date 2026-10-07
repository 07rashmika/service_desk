<?php

namespace Tests\Feature\Tickets;

use App\Actions\Tickets\TransitionTicket;
use App\Enums\TicketState;
use App\Exceptions\InvalidTicketTransition;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->freezeSecond();
    }

    protected function transition(Ticket $ticket, TicketState $to): Ticket
    {
        return app(TransitionTicket::class)->handle($ticket, $to);
    }

    /**
     * @return array<string, array{TicketState, TicketState}>
     */
    public static function allowedTransitions(): array
    {
        return [
            'open → assigned' => [TicketState::Open, TicketState::Assigned],
            'assigned → in progress' => [TicketState::Assigned, TicketState::InProgress],
            'in progress → waiting for user' => [TicketState::InProgress, TicketState::WaitingForUser],
            'in progress → resolved' => [TicketState::InProgress, TicketState::Resolved],
            'waiting for user → in progress' => [TicketState::WaitingForUser, TicketState::InProgress],
            'waiting for user → resolved' => [TicketState::WaitingForUser, TicketState::Resolved],
            'resolved → closed' => [TicketState::Resolved, TicketState::Closed],
            'resolved → in progress (reopen)' => [TicketState::Resolved, TicketState::InProgress],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allowed_transitions_change_the_status(TicketState $from, TicketState $to): void
    {
        $ticket = Ticket::factory()->inState($from)->create();

        $this->transition($ticket, $to);

        $this->assertSame($to, $ticket->fresh()->state());
    }

    /**
     * @return array<string, array{TicketState, TicketState}>
     */
    public static function forbiddenTransitions(): array
    {
        return [
            'open → resolved' => [TicketState::Open, TicketState::Resolved],
            'open → closed' => [TicketState::Open, TicketState::Closed],
            'in progress → closed' => [TicketState::InProgress, TicketState::Closed],
            'closed → in progress' => [TicketState::Closed, TicketState::InProgress],
            'closed → open' => [TicketState::Closed, TicketState::Open],
        ];
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_other_transitions_are_rejected(TicketState $from, TicketState $to): void
    {
        $ticket = Ticket::factory()->inState($from)->create();

        try {
            $this->transition($ticket, $to);
            $this->fail("Expected {$from->value} → {$to->value} to be rejected.");
        } catch (InvalidTicketTransition) {
            $this->assertSame($from, $ticket->fresh()->state());
        }
    }

    public function test_starting_work_records_the_first_response_once(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Assigned)->create();

        $this->transition($ticket, TicketState::InProgress);
        $this->assertTrue($ticket->first_response_at->equalTo(now()));

        $this->travel(2)->hours();
        $this->transition($ticket, TicketState::WaitingForUser);
        $this->transition($ticket, TicketState::InProgress);

        $this->assertTrue($ticket->fresh()->first_response_at->equalTo(now()->subHours(2)));
    }

    public function test_resolving_and_closing_record_their_times(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress)->create(['resolved_at' => null]);

        $this->transition($ticket, TicketState::Resolved);
        $this->assertTrue($ticket->resolved_at->equalTo(now()));

        $this->transition($ticket, TicketState::Closed);
        $this->assertTrue($ticket->closed_at->equalTo(now()));
    }

    public function test_reopening_clears_the_resolved_time(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::Resolved)->create();

        $this->transition($ticket, TicketState::InProgress);

        $this->assertNull($ticket->fresh()->resolved_at);
    }
}
