<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class CreateTicketTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected TicketCategory $category;

    protected TicketPriority $priority;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        Storage::fake('local');

        $this->category = TicketCategory::factory()->create(['name' => 'Network']);
        $this->priority = TicketPriority::factory()->create(['name' => 'High', 'resolution_hours' => 8]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validTicket(array $overrides = []): array
    {
        return [
            'title' => 'Laptop will not connect to the office Wi-Fi',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'description' => 'It sees the network but fails to connect. My phone connects fine.',
            ...$overrides,
        ];
    }

    public function test_report_an_issue_page_lists_active_categories_and_priorities(): void
    {
        TicketCategory::factory()->inactive()->create(['name' => 'Retired category']);

        $this->actingAs($this->employee())
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('Network')
            ->assertSee('High')
            ->assertDontSee('Retired category');
    }

    public function test_employees_can_report_an_issue_with_attachments(): void
    {
        $this->freezeSecond();
        $employee = $this->employee();

        $response = $this->actingAs($employee)->post(route('tickets.store'), $this->validTicket([
            'attachments' => [
                UploadedFile::fake()->image('error.png'),
                UploadedFile::fake()->create('system.log', 4, 'text/plain'),
            ],
        ]));

        $ticket = Ticket::sole();
        $response->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHas('success');

        $this->assertSame('Laptop will not connect to the office Wi-Fi', $ticket->title);
        $this->assertSame(TicketState::Open, $ticket->state());
        $this->assertTrue($ticket->creator->is($employee));
        $this->assertNull($ticket->assigned_to);
        $this->assertTrue($ticket->due_at->equalTo(now()->addHours(8)));

        $this->assertSame(['error.png', 'system.log'], $ticket->attachments->pluck('original_name')->all());
        foreach ($ticket->attachments as $attachment) {
            Storage::disk('local')->assertExists($attachment->path);
            $this->assertStringStartsWith("tickets/{$ticket->id}/", $attachment->path);
        }
    }

    public function test_title_description_category_and_priority_are_required(): void
    {
        $this->actingAs($this->employee())
            ->post(route('tickets.store'), [])
            ->assertSessionHasErrors(['title', 'description', 'category_id', 'priority_id']);

        $this->assertSame(0, Ticket::count());
    }

    public function test_title_must_be_descriptive(): void
    {
        $this->actingAs($this->employee())
            ->post(route('tickets.store'), $this->validTicket(['title' => 'Outlook']))
            ->assertSessionHasErrors('title');
    }

    public function test_inactive_categories_cannot_be_chosen(): void
    {
        $inactive = TicketCategory::factory()->inactive()->create();

        $this->actingAs($this->employee())
            ->post(route('tickets.store'), $this->validTicket(['category_id' => $inactive->id]))
            ->assertSessionHasErrors('category_id');
    }

    public function test_unsafe_file_types_are_rejected(): void
    {
        $this->actingAs($this->employee())
            ->post(route('tickets.store'), $this->validTicket([
                'attachments' => [UploadedFile::fake()->create('installer.exe', 10, 'application/x-msdownload')],
            ]))
            ->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, Ticket::count());
    }

    public function test_files_over_five_megabytes_are_rejected(): void
    {
        $this->actingAs($this->employee())
            ->post(route('tickets.store'), $this->validTicket([
                'attachments' => [UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')],
            ]))
            ->assertSessionHasErrors('attachments.0');
    }

    public function test_no_more_than_five_files_can_be_attached(): void
    {
        $files = collect(range(1, 6))->map(fn (int $number) => UploadedFile::fake()->image("screen-{$number}.png"))->all();

        $this->actingAs($this->employee())
            ->post(route('tickets.store'), $this->validTicket(['attachments' => $files]))
            ->assertSessionHasErrors('attachments');
    }

    public function test_users_without_a_role_cannot_report_issues(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('tickets.create'))->assertForbidden();
        $this->actingAs($user)->post(route('tickets.store'), $this->validTicket())->assertForbidden();
    }
}
