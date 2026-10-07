<?php

namespace Tests\Feature\Admin;

use App\Enums\TicketState;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TechniciansPageTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    public function test_it_lists_technicians_with_their_workload(): void
    {
        $this->withoutVite();
        $this->setUpTicketReferenceData();
        $technician = $this->supportAgent();
        $employee = $this->employee();
        Ticket::factory()->count(2)->inState(TicketState::InProgress, $technician)->create(['due_at' => now()->addDay()]);
        Ticket::factory()->inState(TicketState::InProgress, $technician)->create(['due_at' => now()->subHour()]);

        $response = $this->actingAs($this->admin())->get(route('admin.technicians.index'));

        $response->assertOk()->assertSee($technician->name)->assertDontSee($employee->name);
        $row = $response->viewData('technicians')->firstWhere('id', $technician->id);
        $this->assertSame(3, $row->active_count);
        $this->assertSame(1, $row->overdue_count);
    }
}
