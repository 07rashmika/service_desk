<?php

namespace Tests\Feature\Tickets;

use App\Actions\Tickets\AddTicketComment;
use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\TakeTicket;
use App\Actions\Tickets\TransitionTicket;
use App\Enums\TicketState;
use App\Events\TicketAssigned;
use App\Events\TicketCommentAdded;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Exceptions\InvalidTicketTransition;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

/**
 * The events later phases (notifications, activity log) listen to.
 */
class TicketEventsTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
    }

    public function test_creating_a_ticket_announces_it(): void
    {
        Event::fake([TicketCreated::class]);

        $ticket = app(CreateTicket::class)->handle($this->employee(), [
            'title' => 'Laptop will not start',
            'description' => 'Nothing happens when I press the power button.',
            'category_id' => TicketCategory::factory()->create()->id,
            'priority_id' => TicketPriority::factory()->create()->id,
        ]);

        Event::assertDispatched(TicketCreated::class, fn (TicketCreated $event): bool => $event->ticket->is($ticket));
    }

    public function test_taking_a_ticket_announces_the_assignment_and_the_status_change(): void
    {
        Event::fake([TicketAssigned::class, TicketStatusChanged::class]);
        $ticket = Ticket::factory()->create();
        $technician = $this->supportAgent();

        app(TakeTicket::class)->handle($ticket, $technician);

        Event::assertDispatched(TicketAssigned::class, fn (TicketAssigned $event): bool => $event->technician->is($technician) && $event->isSelfAssigned());
        Event::assertDispatched(TicketStatusChanged::class, fn (TicketStatusChanged $event): bool => $event->from === TicketState::Open && $event->to === TicketState::Assigned);
    }

    public function test_replies_announce_the_comment(): void
    {
        Event::fake([TicketCommentAdded::class]);
        $employee = $this->employee();
        $ticket = Ticket::factory()->for($employee, 'creator')->inState(TicketState::InProgress)->create();

        $comment = app(AddTicketComment::class)->handle($ticket, $employee, 'Still broken after a restart.');

        Event::assertDispatched(TicketCommentAdded::class, fn (TicketCommentAdded $event): bool => $event->comment->is($comment));
    }

    public function test_rejected_status_changes_announce_nothing(): void
    {
        Event::fake([TicketStatusChanged::class]);
        $ticket = Ticket::factory()->create();

        try {
            app(TransitionTicket::class)->handle($ticket, TicketState::Closed);
        } catch (InvalidTicketTransition) {
            // expected
        }

        Event::assertNotDispatched(TicketStatusChanged::class);
    }
}
