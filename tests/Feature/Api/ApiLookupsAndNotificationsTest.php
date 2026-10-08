<?php

namespace Tests\Feature\Api;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Notifications\Tickets\TicketResolved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class ApiLookupsAndNotificationsTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
    }

    public function test_lookups_list_active_categories_and_technicians_for_staff_only(): void
    {
        TicketCategory::factory()->create(['name' => 'Network']);
        TicketCategory::factory()->inactive()->create(['name' => 'Fax']);
        $technician = $this->supportAgent();

        Sanctum::actingAs($this->employee());
        $this->getJson(route('api.v1.lookups'))
            ->assertOk()
            ->assertJsonPath('data.categories.0.name', 'Network')
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonCount(6, 'data.statuses')
            ->assertJsonCount(0, 'data.technicians');

        Sanctum::actingAs($technician);
        $this->getJson(route('api.v1.lookups'))->assertJsonPath('data.technicians.0.id', $technician->id);
    }

    public function test_notifications_can_be_listed_and_marked_read(): void
    {
        $employee = $this->employee();
        $employee->notifyNow(new TicketResolved(Ticket::factory()->for($employee, 'creator')->inState(TicketState::Resolved)->create()), ['database']);
        Sanctum::actingAs($employee);

        $response = $this->getJson(route('api.v1.notifications.index', ['unread' => 1]))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('data.0.read', false);

        $this->postJson(route('api.v1.notifications.read', $response->json('data.0.id')))->assertNoContent();
        $this->getJson(route('api.v1.notifications.index'))->assertJsonPath('unread_count', 0);
    }
}
