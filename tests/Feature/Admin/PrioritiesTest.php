<?php

namespace Tests\Feature\Admin;

use App\Enums\Palette;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class PrioritiesTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $this->admin = $this->admin();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function priorityForm(array $overrides = []): array
    {
        return ['name' => 'Urgent', 'description' => 'Very urgent', 'color' => 'red', 'level' => 5, 'response_hours' => 1, 'resolution_hours' => 2, ...$overrides];
    }

    public function test_priorities_can_be_added_with_their_sla_targets(): void
    {
        $this->actingAs($this->admin)->post(route('admin.priorities.store'), $this->priorityForm())->assertSessionHas('success');

        $priority = TicketPriority::where('name', 'Urgent')->sole();
        $this->assertSame('urgent', $priority->slug);
        $this->assertSame(Palette::Red, $priority->color);
        $this->assertSame(2, $priority->resolution_hours);
    }

    public function test_sla_changes_apply_to_new_tickets_only(): void
    {
        $priority = TicketPriority::factory()->create(['resolution_hours' => 8]);
        $ticket = Ticket::factory()->for($priority, 'priority')->create();
        $originalDeadline = $ticket->due_at->toDateTimeString();

        $this->actingAs($this->admin)
            ->put(route('admin.priorities.update', $priority), $this->priorityForm(['name' => $priority->name, 'resolution_hours' => 4]))
            ->assertSessionHas('success');

        $this->assertSame(4, $priority->fresh()->resolution_hours);
        $this->assertSame($originalDeadline, $ticket->fresh()->due_at->toDateTimeString());
    }

    public function test_first_response_cannot_be_longer_than_resolution(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.priorities.store'), $this->priorityForm(['response_hours' => 10, 'resolution_hours' => 5]))
            ->assertSessionHasErrorsIn('createPriority', 'response_hours');
    }

    public function test_priorities_used_by_tickets_cannot_be_deleted(): void
    {
        $used = TicketPriority::factory()->create();
        Ticket::factory()->for($used, 'priority')->create();

        $this->actingAs($this->admin)->delete(route('admin.priorities.destroy', $used))->assertSessionHas('error');
        $this->assertModelExists($used);
    }
}
