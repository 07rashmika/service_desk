<?php

namespace Tests\Feature\Sla;

use App\Enums\TicketState;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Tickets\TicketDueSoon;
use App\Notifications\Tickets\TicketOverdue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class CheckTicketSlaTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected User $technician;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        Notification::fake();
        $this->technician = $this->supportAgent();
        $this->admin = $this->admin();
    }

    public function test_technicians_are_warned_once_shortly_before_the_deadline(): void
    {
        $dueSoon = Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create(['due_at' => now()->addMinutes(30)]);
        Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create(['due_at' => now()->addHours(5)]);

        $this->artisan('tickets:check-sla')->assertSuccessful();
        $this->artisan('tickets:check-sla')->assertSuccessful();

        Notification::assertSentToTimes($this->technician, TicketDueSoon::class, 1);
        $this->assertNotNull($dueSoon->fresh()->sla_warning_sent_at);
    }

    public function test_missed_deadlines_alert_the_technician_and_admins_once(): void
    {
        $ticket = Ticket::factory()->inState(TicketState::InProgress, $this->technician)->create(['due_at' => now()->subMinutes(5)]);

        $this->artisan('tickets:check-sla')->assertSuccessful();
        $this->artisan('tickets:check-sla')->assertSuccessful();

        Notification::assertSentToTimes($this->technician, TicketOverdue::class, 1);
        Notification::assertSentToTimes($this->admin, TicketOverdue::class, 1);
        $this->assertTrue(ActivityLog::query()->forSubject($ticket)->where('event', 'ticket.sla_breached')->exists());
    }

    public function test_unassigned_overdue_tickets_alert_the_admins(): void
    {
        Ticket::factory()->create(['due_at' => now()->subHour()]);

        $this->artisan('tickets:check-sla');

        Notification::assertSentTo($this->admin, TicketOverdue::class);
        Notification::assertNotSentTo($this->technician, TicketOverdue::class);
    }

    public function test_tickets_waiting_on_the_requester_are_not_overdue(): void
    {
        Ticket::factory()->inState(TicketState::WaitingForUser, $this->technician)->create(['due_at' => now()->subHour()]);

        $this->artisan('tickets:check-sla');

        Notification::assertNothingSent();
    }

    public function test_the_checks_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('tickets:check-sla')
            ->expectsOutputToContain('tickets:auto-close');
    }
}
