<?php

namespace Tests\Feature\Reports;

use App\Enums\TicketState;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Reports\TicketReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketReportTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->travelTo(now()->setTime(12, 0));
    }

    public function test_the_summary_counts_created_and_resolved_tickets_and_sla_results(): void
    {
        // Resolved within the deadline: 4 hours.
        Ticket::factory()->inState(TicketState::Resolved)->create([
            'created_at' => now()->subHours(6), 'resolved_at' => now()->subHours(2), 'due_at' => now()->subHour(),
        ]);
        // Resolved after the deadline: 8 hours.
        Ticket::factory()->inState(TicketState::Resolved)->create([
            'created_at' => now()->subHours(9), 'resolved_at' => now()->subHour(), 'due_at' => now()->subHours(5),
        ]);
        Ticket::factory()->create(['created_at' => now()->subDay()]);
        Ticket::factory()->create(['created_at' => now()->subDays(40)]);

        $summary = (new TicketReport(30))->summary();

        $this->assertSame(3, $summary['created']);
        $this->assertSame(2, $summary['resolved']);
        $this->assertSame(6.0, $summary['averageResolutionHours']);
        $this->assertSame(50.0, $summary['slaCompliance']);
    }

    public function test_daily_volume_has_one_point_per_day(): void
    {
        Ticket::factory()->count(2)->create(['created_at' => now()->subDays(2)]);
        Ticket::factory()->inState(TicketState::Resolved)->create(['created_at' => now()->subDays(3), 'resolved_at' => now()]);

        $volume = (new TicketReport(7))->dailyVolume();

        $this->assertCount(7, $volume['labels']);
        $this->assertSame(2, $volume['created'][4]);
        $this->assertSame(1, $volume['resolved'][6]);
        $this->assertSame(3, array_sum($volume['created']));
    }

    public function test_tickets_are_grouped_by_the_requesters_department(): void
    {
        $finance = Department::factory()->create(['name' => 'Finance']);
        Ticket::factory()->count(2)->for(User::factory()->for($finance), 'creator')->create();
        Ticket::factory()->for(User::factory(), 'creator')->create();

        $byDepartment = (new TicketReport(30))->byDepartment()->keyBy('label');

        $this->assertSame(2, $byDepartment['Finance']['count']);
        $this->assertSame(1, $byDepartment['No department']['count']);
    }

    public function test_the_previous_period_is_the_same_length_just_before(): void
    {
        $report = new TicketReport(30);
        $previous = $report->previous();

        $this->assertTrue($previous->to->lt($report->from));
        $this->assertSame(29, (int) $previous->from->diffInDays($previous->to));
    }

    public function test_deltas_say_whether_the_change_is_good(): void
    {
        $this->assertSame(['text' => '+25%', 'tone' => 'good'], TicketReport::delta(10, 8, higherIsBetter: true));
        $this->assertSame(['text' => '+2.5h', 'tone' => 'bad'], TicketReport::delta(8.5, 6, higherIsBetter: false, unit: 'h'));
        $this->assertSame(['text' => '-4 pts', 'tone' => 'bad'], TicketReport::delta(90, 94, higherIsBetter: true, unit: ' pts'));
        $this->assertNull(TicketReport::delta(5, 0, higherIsBetter: true));
        $this->assertNull(TicketReport::delta(null, 4, higherIsBetter: true));
    }
}
