<?php

namespace Tests\Feature\Api;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class ApiTicketsTest extends TestCase
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

    public function test_employees_list_only_their_own_tickets(): void
    {
        $mine = Ticket::factory()->for($this->employee, 'creator')->create();
        Ticket::factory()->create();
        Sanctum::actingAs($this->employee);

        $this->getJson(route('api.v1.tickets.index', ['scope' => 'all']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', $mine->reference)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_staff_can_pick_a_scope_and_filter(): void
    {
        $technician = $this->supportAgent();
        Ticket::factory()->create(['title' => 'Unassigned one']);
        Ticket::factory()->inState(TicketState::InProgress, $technician)->create(['title' => 'Mine in progress']);
        Sanctum::actingAs($technician);

        $this->getJson(route('api.v1.tickets.index', ['scope' => 'mine']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mine in progress')
            ->assertJsonPath('data.0.can.resolve', true);

        $this->getJson(route('api.v1.tickets.index', ['scope' => 'unassigned', 'status' => 'open']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.can.take', true);
    }

    public function test_tickets_can_be_reported_with_attachments(): void
    {
        Sanctum::actingAs($this->employee);

        $response = $this->postJson(route('api.v1.tickets.store'), [
            'title' => 'Laptop will not connect to Wi-Fi',
            'description' => 'It sees the network but cannot connect at all since this morning.',
            'category_id' => TicketCategory::factory()->create()->id,
            'priority_id' => TicketPriority::factory()->create()->id,
            'attachments' => [UploadedFile::fake()->image('error.png')],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status.slug', 'open')
            ->assertJsonPath('data.requester.id', $this->employee->id)
            ->assertJsonPath('data.attachments.0.name', 'error.png')
            ->assertJsonPath('data.attachments.0.is_image', true);

        $this->assertSame(1, Ticket::count());
    }

    public function test_invalid_tickets_get_validation_errors_as_json(): void
    {
        Sanctum::actingAs($this->employee);

        $this->postJson(route('api.v1.tickets.store'), ['title' => 'Hi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description', 'category_id', 'priority_id']);
    }

    public function test_employees_cannot_log_tickets_for_someone_else(): void
    {
        Sanctum::actingAs($this->employee);

        $this->postJson(route('api.v1.tickets.store'), ['requester_id' => $this->supportAgent()->id])
            ->assertJsonValidationErrors('requester_id');
    }

    public function test_the_ticket_detail_hides_internal_notes_from_the_requester(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::InProgress)->create();
        TicketComment::factory()->for($ticket)->create(['body' => 'Please restart.']);
        TicketComment::factory()->internal()->for($ticket)->create(['body' => 'Under warranty.']);

        Sanctum::actingAs($this->employee);
        $this->getJson(route('api.v1.tickets.show', $ticket))
            ->assertOk()
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.body', 'Please restart.')
            ->assertJsonPath('data.can.take', false);

        Sanctum::actingAs($this->supportAgent());
        $this->getJson(route('api.v1.tickets.show', $ticket))->assertJsonCount(2, 'data.comments');
    }

    public function test_other_peoples_tickets_are_forbidden_and_missing_ones_are_not_found(): void
    {
        Sanctum::actingAs($this->employee);

        $this->getJson(route('api.v1.tickets.show', Ticket::factory()->create()))->assertForbidden();
        $this->getJson('/api/v1/tickets/999999')->assertNotFound()->assertExactJson(['message' => 'Not found.']);
    }

    public function test_replies_and_internal_notes(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::InProgress)->create();

        Sanctum::actingAs($this->employee);
        $this->postJson(route('api.v1.tickets.comments.store', $ticket), ['body' => 'Still broken.'])
            ->assertCreated()
            ->assertJsonPath('data.internal', false)
            ->assertJsonPath('data.author.id', $this->employee->id);
        $this->postJson(route('api.v1.tickets.comments.store', $ticket), ['body' => 'Sneaky', 'internal' => true])->assertForbidden();

        Sanctum::actingAs($this->supportAgent());
        $this->postJson(route('api.v1.tickets.comments.store', $ticket), ['body' => 'Checked the logs.', 'internal' => true])
            ->assertCreated()
            ->assertJsonPath('data.internal', true);
    }

    public function test_attachments_download_with_a_token(): void
    {
        $ticket = Ticket::factory()->for($this->employee, 'creator')->create();
        $attachment = $ticket->attachments()->create([
            'user_id' => $this->employee->id, 'original_name' => 'log.txt', 'disk' => 'local',
            'path' => 'tickets/'.$ticket->id.'/log.txt', 'mime_type' => 'text/plain', 'size' => 5,
        ]);
        Storage::disk('local')->put($attachment->path, 'hello');

        Sanctum::actingAs($this->employee);
        $this->get(route('api.v1.attachments.show', $attachment))->assertOk()->assertDownload('log.txt');

        Sanctum::actingAs($this->employee());
        $this->getJson(route('api.v1.attachments.show', $attachment))->assertForbidden();
    }
}
