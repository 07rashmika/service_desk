<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        Storage::fake('local');
        $this->employee = $this->employee();
    }

    public function test_requesters_can_reply_with_attachments(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::InProgress)->create();

        $this->actingAs($this->employee)
            ->post(route('tickets.comments.store', $ticket), [
                'body' => 'Here is the screenshot you asked for.',
                'attachments' => [UploadedFile::fake()->image('screenshot.png')],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $comment = $ticket->comments()->sole();
        $this->assertSame('Here is the screenshot you asked for.', $comment->body);
        $this->assertFalse($comment->is_internal);
        $this->assertSame('screenshot.png', $comment->attachments()->sole()->original_name);
        $this->assertSame(TicketState::InProgress, $ticket->fresh()->state());
    }

    public function test_replying_to_a_ticket_waiting_on_the_requester_puts_it_back_in_progress(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::WaitingForUser)->create();

        $this->actingAs($this->employee)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Restarting fixed it for now.']);

        $this->assertSame(TicketState::InProgress, $ticket->fresh()->state());
    }

    public function test_a_reply_cannot_be_empty(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->create();

        $this->actingAs($this->employee)
            ->post(route('tickets.comments.store', $ticket), ['body' => ''])
            ->assertSessionHasErrors('body');
    }

    public function test_closed_tickets_cannot_be_replied_to(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::Closed)->create();

        $this->actingAs($this->employee)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Hello?'])
            ->assertForbidden();
    }

    public function test_employees_cannot_reply_to_other_peoples_tickets(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->employee)
            ->post(route('tickets.comments.store', $ticket), ['body' => 'Sneaky reply'])
            ->assertForbidden();

        $this->assertSame(0, $ticket->comments()->count());
    }
}
