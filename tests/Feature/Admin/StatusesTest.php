<?php

namespace Tests\Feature\Admin;

use App\Enums\Palette;
use App\Enums\TicketState;
use App\Models\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class StatusesTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    public function test_statuses_can_be_renamed_and_recoloured_but_keep_their_workflow_step(): void
    {
        $this->setUpTicketReferenceData();
        $status = TicketStatus::for(TicketState::WaitingForUser);

        $this->actingAs($this->admin())
            ->put(route('admin.statuses.update', $status), ['name' => 'Awaiting reply', 'color' => 'violet', 'slug' => 'hacked'])
            ->assertSessionHas('success');

        $status->refresh();
        $this->assertSame('Awaiting reply', $status->name);
        $this->assertSame(Palette::Violet, $status->color);
        $this->assertSame(TicketState::WaitingForUser, $status->slug);
    }
}
