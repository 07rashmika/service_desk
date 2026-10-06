<?php

namespace Tests\Feature\Tickets;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketListTest extends TestCase
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

    public function test_employees_only_see_their_own_tickets(): void
    {
        Ticket::factory()->for($this->employee, 'creator')->create(['title' => 'My printer is jammed again']);
        Ticket::factory()->create(['title' => 'Someone else\'s laptop is broken']);

        $this->actingAs($this->employee)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('My printer is jammed again')
            ->assertDontSee('Someone else\'s laptop is broken');
    }

    public function test_tabs_filter_by_workflow_state_and_show_counts(): void
    {
        Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::WaitingForUser)->create(['title' => 'Waiting on my reply']);
        Ticket::factory()->for($this->employee, 'creator')->inState(TicketState::Closed)->create(['title' => 'Old closed ticket']);

        $response = $this->actingAs($this->employee)->get(route('tickets.index', ['tab' => 'waiting']));

        $response->assertOk()
            ->assertSee('Waiting on my reply')
            ->assertDontSee('Old closed ticket');

        $tabs = collect($response->viewData('tabs'))->keyBy('label');
        $this->assertSame(2, $tabs['All']['count']);
        $this->assertSame(1, $tabs['Waiting for me']['count']);
        $this->assertTrue($tabs['Waiting for me']['active']);
    }

    public function test_tickets_can_be_found_by_reference_or_keyword(): void
    {
        $vpn = Ticket::factory()->for($this->employee, 'creator')->create(['title' => 'VPN drops every 15 minutes']);
        Ticket::factory()->for($this->employee, 'creator')->create(['title' => 'Need a second monitor']);

        $this->actingAs($this->employee)
            ->get(route('tickets.index', ['search' => 'vpn']))
            ->assertSee('VPN drops every 15 minutes')
            ->assertDontSee('Need a second monitor');

        $this->actingAs($this->employee)
            ->get(route('tickets.index', ['search' => $vpn->reference]))
            ->assertSee('VPN drops every 15 minutes')
            ->assertDontSee('Need a second monitor');
    }

    public function test_tickets_can_be_filtered_by_category(): void
    {
        $network = TicketCategory::factory()->create();
        Ticket::factory()->for($this->employee, 'creator')->for($network, 'category')->create(['title' => 'Wi-Fi keeps dropping']);
        Ticket::factory()->for($this->employee, 'creator')->create(['title' => 'Keyboard keys are stuck']);

        $this->actingAs($this->employee)
            ->get(route('tickets.index', ['category' => $network->id]))
            ->assertSee('Wi-Fi keeps dropping')
            ->assertDontSee('Keyboard keys are stuck');
    }

    public function test_long_lists_are_paginated(): void
    {
        Ticket::factory()->count(17)->for($this->employee, 'creator')->create();

        $response = $this->actingAs($this->employee)->get(route('tickets.index'));

        $this->assertCount(15, $response->viewData('tickets'));
        $response->assertSee('rel="next"', false);
    }

    public function test_a_friendly_message_is_shown_when_there_are_no_tickets(): void
    {
        $this->actingAs($this->employee)
            ->get(route('tickets.index'))
            ->assertSee('No tickets yet');

        $this->actingAs($this->employee)
            ->get(route('tickets.index', ['search' => 'nothing matches this']))
            ->assertSee('No tickets found');
    }
}
