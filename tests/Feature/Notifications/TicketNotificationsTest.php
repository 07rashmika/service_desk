<?php

namespace Tests\Feature\Notifications;

use App\Actions\Tickets\AddTicketComment;
use App\Actions\Tickets\AskRequesterForInfo;
use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\CreateTicket;
use App\Actions\Tickets\ResolveTicket;
use App\Actions\Tickets\TakeTicket;
use App\Actions\Tickets\TransitionTicket;
use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use App\Notifications\Tickets\TicketAssignedToYou;
use App\Notifications\Tickets\TicketClosedByRequester;
use App\Notifications\Tickets\TicketLoggedForYou;
use App\Notifications\Tickets\TicketPickedUp;
use App\Notifications\Tickets\TicketReopened;
use App\Notifications\Tickets\TicketReplyAdded;
use App\Notifications\Tickets\TicketResolved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

/**
 * Who gets told about what. The rule: the other side of the conversation is
 * notified, never the person who did the thing.
 */
class TicketNotificationsTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $requester;

    protected User $technician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        Notification::fake();

        $this->requester = $this->employee();
        $this->technician = $this->supportAgent();
    }

    protected function ticketIn(TicketState $state): Ticket
    {
        return Ticket::factory()->for($this->requester, 'creator')->inState($state, $this->technician)->create();
    }

    public function test_assigning_tells_the_technician_and_the_requester(): void
    {
        $dispatcher = $this->supportAgent();
        $ticket = Ticket::factory()->for($this->requester, 'creator')->create();

        app(AssignTicket::class)->handle($ticket, $this->technician, $dispatcher, 'Caller is on floor 3.');

        Notification::assertSentTo($this->technician, TicketAssignedToYou::class, fn (TicketAssignedToYou $notification): bool => $notification->note === 'Caller is on floor 3.');
        Notification::assertSentTo($this->requester, TicketPickedUp::class);
        Notification::assertNothingSentTo($dispatcher);
    }

    public function test_taking_a_ticket_only_tells_the_requester(): void
    {
        $ticket = Ticket::factory()->for($this->requester, 'creator')->create();

        app(TakeTicket::class)->handle($ticket, $this->technician);

        Notification::assertNotSentTo($this->technician, TicketAssignedToYou::class);
        Notification::assertSentTo($this->requester, TicketPickedUp::class);
    }

    public function test_a_technicians_reply_goes_to_the_requester_and_not_back_to_the_author(): void
    {
        $ticket = $this->ticketIn(TicketState::InProgress);

        app(AddTicketComment::class)->handle($ticket, $this->technician, 'Please restart and try again.');

        Notification::assertSentTo($this->requester, TicketReplyAdded::class);
        Notification::assertNotSentTo($this->technician, TicketReplyAdded::class);
    }

    public function test_the_requesters_reply_goes_to_the_assigned_technician(): void
    {
        $ticket = $this->ticketIn(TicketState::WaitingForUser);

        app(AddTicketComment::class)->handle($ticket, $this->requester, 'Restarting did not help.');

        Notification::assertSentTo($this->technician, TicketReplyAdded::class);
        Notification::assertNotSentTo($this->requester, TicketReplyAdded::class);
    }

    public function test_internal_notes_never_reach_the_requester(): void
    {
        $colleague = $this->supportAgent();
        $ticket = $this->ticketIn(TicketState::InProgress);

        app(AddTicketComment::class)->handle($ticket, $colleague, 'Device is under warranty.', internal: true);

        Notification::assertNotSentTo($this->requester, TicketReplyAdded::class);
        Notification::assertSentTo($this->technician, TicketReplyAdded::class, fn (TicketReplyAdded $notification): bool => str_contains($notification->message($this->technician), 'internal note'));
    }

    public function test_a_question_to_the_requester_says_more_information_is_needed(): void
    {
        $ticket = $this->ticketIn(TicketState::InProgress);

        app(AskRequesterForInfo::class)->handle($ticket, $this->technician, 'Could you send a screenshot?');

        Notification::assertSentTo($this->requester, TicketReplyAdded::class, fn (TicketReplyAdded $notification): bool => str_contains($notification->message($this->requester), 'needs more information'));
    }

    public function test_resolving_asks_the_requester_to_confirm(): void
    {
        $ticket = $this->ticketIn(TicketState::InProgress);

        app(ResolveTicket::class)->handle($ticket, 'Replaced the faulty cable.');

        Notification::assertSentTo($this->requester, TicketResolved::class);
    }

    public function test_reopening_and_closing_tell_the_technician(): void
    {
        $reopened = $this->ticketIn(TicketState::Resolved);
        $closed = $this->ticketIn(TicketState::Resolved);

        app(TransitionTicket::class)->handle($reopened, TicketState::InProgress, $this->requester);
        app(TransitionTicket::class)->handle($closed, TicketState::Closed, $this->requester);

        Notification::assertSentTo($this->technician, TicketReopened::class);
        Notification::assertSentTo($this->technician, TicketClosedByRequester::class);
    }

    public function test_employees_hear_about_tickets_logged_for_them(): void
    {
        app(CreateTicket::class)->handle($this->requester, [
            'title' => 'Phoned in: printer jammed',
            'description' => 'Caller says the printer on floor 4 is jammed.',
            'category_id' => TicketCategory::factory()->create()->id,
            'priority_id' => TicketPriority::factory()->create()->id,
        ], loggedBy: $this->technician);

        Notification::assertSentTo($this->requester, TicketLoggedForYou::class);
    }

    public function test_notifications_are_stored_pushed_live_and_emailed(): void
    {
        $notification = new TicketResolved($this->ticketIn(TicketState::Resolved));

        $this->assertSame(['database', 'broadcast', 'mail'], $notification->via($this->requester));
    }

    public function test_switched_off_emails_still_appear_in_the_app(): void
    {
        $this->requester->forceFill(['notification_preferences' => ['ticket_updates' => false]])->save();
        $notification = new TicketResolved($this->ticketIn(TicketState::Resolved));

        $this->assertSame(['database', 'broadcast'], $notification->via($this->requester->fresh()));
    }

    public function test_deactivated_people_are_not_notified(): void
    {
        $this->requester->forceFill(['is_active' => false])->save();
        $notification = new TicketResolved($this->ticketIn(TicketState::Resolved));

        $this->assertSame([], $notification->via($this->requester->fresh()));
    }
}
