<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class LogTicketOnBehalfTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $technician;

    protected User $caller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        Storage::fake('local');

        $this->technician = $this->supportAgent();
        $this->caller = $this->employee();
        $this->caller->update(['name' => 'Nimal Perera']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function ticketDetails(array $overrides = []): array
    {
        return [
            'title' => 'Phoned in: printer on floor 4 is jammed',
            'category_id' => TicketCategory::factory()->create()->id,
            'priority_id' => TicketPriority::factory()->create()->id,
            'description' => 'Caller says the printer shows a paper jam error even after clearing the tray.',
            ...$overrides,
        ];
    }

    public function test_staff_see_the_requester_field_and_employees_do_not(): void
    {
        $this->actingAs($this->technician)
            ->get(route('tickets.create'))
            ->assertSee('Who is this ticket for?')
            ->assertSee('Nimal Perera');

        $this->actingAs($this->caller)
            ->get(route('tickets.create'))
            ->assertDontSee('Who is this ticket for?');
    }

    public function test_staff_can_log_a_ticket_for_an_employee(): void
    {
        $response = $this->actingAs($this->technician)->post(route('tickets.store'), $this->ticketDetails([
            'requester_id' => $this->caller->id,
            'attachments' => [UploadedFile::fake()->image('jam.png')],
        ]));

        $ticket = Ticket::sole();
        $response->assertRedirect(route('support.tickets.show', $ticket))
            ->assertSessionHas('success', "Ticket {$ticket->reference} was logged for Nimal Perera. They can follow it under My Tickets.");

        $this->assertTrue($ticket->creator->is($this->caller));
        $this->assertTrue($ticket->loggedBy->is($this->technician));
        $this->assertSame($this->technician->id, $ticket->attachments()->sole()->user_id);
    }

    public function test_the_employee_sees_the_logged_ticket_as_their_own(): void
    {
        $this->actingAs($this->technician)->post(route('tickets.store'), $this->ticketDetails(['requester_id' => $this->caller->id]));
        $ticket = Ticket::sole();

        $this->actingAs($this->caller)
            ->get(route('tickets.index'))
            ->assertSee('Phoned in: printer on floor 4 is jammed');

        $this->actingAs($this->caller)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Logged by '.$this->technician->name.' on their behalf');
    }

    public function test_staff_reporting_their_own_issue_are_not_recorded_as_logging_it(): void
    {
        $this->actingAs($this->technician)->post(route('tickets.store'), $this->ticketDetails())
            ->assertRedirect(route('tickets.show', Ticket::sole()));

        $ticket = Ticket::sole();
        $this->assertTrue($ticket->creator->is($this->technician));
        $this->assertNull($ticket->logged_by);
    }

    public function test_employees_cannot_log_tickets_for_someone_else(): void
    {
        $this->actingAs($this->caller)
            ->post(route('tickets.store'), $this->ticketDetails(['requester_id' => $this->technician->id]))
            ->assertSessionHasErrors('requester_id');

        $this->assertSame(0, Ticket::count());
    }

    public function test_tickets_cannot_be_logged_for_deactivated_users(): void
    {
        $former = User::factory()->inactive()->create();

        $this->actingAs($this->technician)
            ->post(route('tickets.store'), $this->ticketDetails(['requester_id' => $former->id]))
            ->assertSessionHasErrors('requester_id');
    }
}
