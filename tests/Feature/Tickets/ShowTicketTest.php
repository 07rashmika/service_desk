<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class ShowTicketTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $this->employee = $this->employee();
    }

    public function test_requesters_can_see_their_ticket_and_the_public_conversation(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::InProgress)->create([
            'title' => 'Outlook keeps asking for my password',
        ]);
        TicketComment::factory()->for($ticket)->create(['body' => 'Please restart Outlook and try again.']);

        $this->actingAs($this->employee)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Outlook keeps asking for my password')
            ->assertSee($ticket->reference)
            ->assertSee('Please restart Outlook and try again.')
            ->assertSee('Send reply');
    }

    public function test_internal_notes_are_hidden_from_the_requester_but_visible_to_support(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::InProgress)->create();
        TicketComment::factory()->internal()->for($ticket)->create(['body' => 'Device is still under warranty.']);

        $this->actingAs($this->employee)
            ->get(route('tickets.show', $ticket))
            ->assertDontSee('Device is still under warranty.');

        $support = $this->supportAgent();

        $this->actingAs($support)
            ->get(route('tickets.show', $ticket))
            ->assertRedirect(route('support.tickets.show', $ticket));

        $this->actingAs($support)
            ->get(route('support.tickets.show', $ticket))
            ->assertSee('Device is still under warranty.')
            ->assertSee('Internal note');
    }

    public function test_employees_cannot_open_other_peoples_tickets(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_deleted_tickets_are_not_found(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->create();
        $ticket->delete();

        $this->actingAs($this->employee)
            ->get(route('tickets.show', $ticket))
            ->assertNotFound();
    }

    public function test_resolved_tickets_ask_the_requester_to_confirm_the_fix(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::Resolved)->create([
            'solution' => 'Cleared the cached credentials.',
        ]);

        $this->actingAs($this->employee)
            ->get(route('tickets.show', $ticket))
            ->assertSee('marked this ticket as resolved')
            ->assertSee('Cleared the cached credentials.')
            ->assertSee('Confirm &amp; close ticket', false)
            ->assertSee('Not fixed – reopen');
    }

    public function test_closed_tickets_cannot_be_replied_to(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::Closed)->create();

        $this->actingAs($this->employee)
            ->get(route('tickets.show', $ticket))
            ->assertSee('This ticket is closed')
            ->assertDontSee('Send reply');
    }

    public function test_image_attachments_can_be_previewed_and_downloaded(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->create();
        $image = TicketAttachment::factory()->for($ticket)->create(['original_name' => 'error.png', 'mime_type' => 'image/png']);

        $this->actingAs($this->employee)
            ->get(route('tickets.show', $ticket))
            ->assertSee('data-image-preview="ticket-'.$ticket->id.'"', false)
            ->assertSee('data-src="'.route('attachments.show', [$image, 'inline' => 1]).'"', false)
            ->assertSee('data-download="'.route('attachments.show', $image).'"', false)
            ->assertSee('Click to preview');
    }

    public function test_other_files_are_offered_as_downloads_only(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->create();
        $pdf = TicketAttachment::factory()->for($ticket)->create(['original_name' => 'invoice.pdf', 'mime_type' => 'application/pdf']);

        $this->actingAs($this->employee)
            ->get(route('tickets.show', $ticket))
            ->assertSee('invoice.pdf')
            ->assertSee('href="'.route('attachments.show', $pdf).'"', false)
            ->assertDontSee('data-image-preview="ticket-', false);
    }
}
