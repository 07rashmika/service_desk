<?php

namespace Tests\Feature\Reports;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Reports\TicketReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class TicketReportCacheTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
        $this->travelTo(now()->setTime(12, 0));

        // Serialize like the real cache does, which refuses to rebuild PHP objects.
        config(['cache.stores.array.serialize' => true]);
        $this->app['cache']->forgetDriver('array');
    }

    public function test_repeat_views_reuse_the_cached_numbers(): void
    {
        Ticket::factory()->count(2)->create();
        (new TicketReport(30))->summary();

        DB::enableQueryLog();
        $summary = (new TicketReport(30))->summary();

        $this->assertSame(2, $summary['created']);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_changing_a_ticket_refreshes_the_numbers_straight_away(): void
    {
        $ticket = Ticket::factory()->create();
        $this->assertSame(1, (new TicketReport(30))->summary()['open']);

        Ticket::factory()->create();
        $this->assertSame(2, (new TicketReport(30))->summary()['open']);

        $ticket->update(['status_id' => TicketStatus::for(TicketState::Closed)->id]);
        $this->assertSame(1, (new TicketReport(30))->summary()['open']);

        $ticket->delete();
        $this->assertSame(1, (new TicketReport(30))->summary()['created']);
    }

    public function test_renaming_a_status_refreshes_the_breakdown(): void
    {
        Ticket::factory()->create();
        (new TicketReport(30))->byStatus();

        TicketStatus::for(TicketState::Open)->update(['name' => 'New']);

        $this->assertSame('New', (new TicketReport(30))->byStatus()->first()['status']->name);
    }

    public function test_cached_numbers_expire_after_a_few_minutes(): void
    {
        $ticket = Ticket::factory()->create();
        (new TicketReport(30))->summary();

        // A change that skips model events, e.g. a manual database fix.
        Ticket::query()->toBase()->insert(Arr::except($ticket->getAttributes(), 'id'));
        $this->assertSame(1, (new TicketReport(30))->summary()['created']);

        $this->travel(TicketReport::CACHE_MINUTES + 1)->minutes();
        $this->assertSame(2, (new TicketReport(30))->summary()['created']);
    }

    public function test_every_section_comes_back_from_the_cache_intact(): void
    {
        $technician = $this->supportAgent();
        Ticket::factory()->inState(TicketState::Resolved)->create([
            'assigned_to' => $technician->id, 'created_at' => now()->subHours(3), 'resolved_at' => now()->subHour(), 'due_at' => now(),
        ]);

        $first = $this->allSections();
        $second = $this->allSections();

        $this->assertEquals($first, $second);
        $this->assertTrue($second['technicians']->first()['technician']->is($technician));
        $this->assertSame(1, $second['technicians']->first()['resolved']);
        $this->assertSame(100.0, $second['slaByPriority']->firstWhere('resolved', 1)['compliance']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function allSections(): array
    {
        $report = new TicketReport(30);

        return [
            'summary' => $report->summary(),
            'dailyVolume' => $report->dailyVolume(),
            'byStatus' => $report->byStatus(),
            'byCategory' => $report->byCategory(),
            'byDepartment' => $report->byDepartment(),
            'slaByPriority' => $report->slaByPriority(),
            'technicians' => $report->technicians(),
        ];
    }

    public function test_different_date_ranges_are_cached_separately(): void
    {
        Ticket::factory()->create(['created_at' => now()->subDays(20)]);

        $this->assertSame(0, (new TicketReport(7))->summary()['created']);
        $this->assertSame(1, (new TicketReport(30))->summary()['created']);
    }
}
