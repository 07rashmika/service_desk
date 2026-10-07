<?php

namespace Tests\Feature\Notifications;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Tickets\TicketResolved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class NotificationsPageTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $requester;

    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $this->requester = $this->employee();
        $this->ticket = Ticket::factory()->for($this->requester, 'creator')->inState(TicketState::Resolved)->create();
        $this->requester->notifyNow(new TicketResolved($this->ticket), ['database']);
    }

    public function test_the_page_lists_notifications_and_the_bell_shows_the_unread_count(): void
    {
        $response = $this->actingAs($this->requester)->get(route('notifications.index'));

        $response->assertOk()
            ->assertSee("{$this->ticket->reference} was resolved. Please confirm the fix")
            ->assertSee('Today');
        $this->assertSame(1, $response->viewData('unreadCount'));
        $this->assertStringContainsString('count: 1', $response->getContent());
    }

    public function test_opening_a_notification_marks_it_read_and_goes_to_the_ticket(): void
    {
        $notification = $this->requester->notifications()->sole();

        $this->actingAs($this->requester)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('tickets.show', $this->ticket));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_people_cannot_open_someone_elses_notification(): void
    {
        $notification = $this->requester->notifications()->sole();

        $this->actingAs($this->employee())
            ->get(route('notifications.open', $notification->id))
            ->assertNotFound();
    }

    public function test_everything_can_be_marked_as_read(): void
    {
        $this->actingAs($this->requester)->post(route('notifications.read-all'))->assertSessionHas('success');

        $this->assertSame(0, $this->requester->unreadNotifications()->count());
        $this->actingAs($this->requester)->get(route('notifications.index', ['tab' => 'unread']))->assertSee('No unread notifications');
    }
}
