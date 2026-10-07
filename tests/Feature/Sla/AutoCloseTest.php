<?php

namespace Tests\Feature\Sla;

use App\Enums\TicketState;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Notifications\Tickets\TicketAutoClosed;
use App\Notifications\Tickets\TicketClosedByRequester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class AutoCloseTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    public function test_resolved_tickets_close_automatically_after_three_days_without_a_response(): void
    {
        $this->setUpTicketReferenceData();
        Notification::fake();
        $technician = $this->supportAgent();
        $old = Ticket::factory()->inState(TicketState::Resolved, $technician)->create(['resolved_at' => now()->subDays(3)->subMinute(), 'closed_at' => null]);
        Ticket::factory()->inState(TicketState::Resolved, $technician)->create(['resolved_at' => now()->subDays(5), 'closed_at' => null]);
        $recent = Ticket::factory()->inState(TicketState::Resolved, $technician)->create(['resolved_at' => now()->subDay(), 'closed_at' => null]);

        $this->artisan('tickets:auto-close')->assertSuccessful();

        $this->assertSame(TicketState::Closed, $old->fresh()->state());
        $this->assertNotNull($old->fresh()->closed_at);
        $this->assertSame(TicketState::Resolved, $recent->fresh()->state());

        Notification::assertSentTo($old->creator, TicketAutoClosed::class);
        Notification::assertNotSentTo($technician, TicketClosedByRequester::class);

        $entry = ActivityLog::query()->forSubject($old)->where('event', 'ticket.status_changed')->sole();
        $this->assertNull($entry->user_id);
        $this->assertStringStartsWith('Closed automatically', $entry->description);
    }
}
