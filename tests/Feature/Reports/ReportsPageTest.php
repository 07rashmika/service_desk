<?php

namespace Tests\Feature\Reports;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
    }

    public function test_admins_and_support_staff_can_see_reports(): void
    {
        Ticket::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Tickets created vs resolved')
            ->assertSee('Technician performance')
            ->assertSee('Export CSV');

        $this->actingAs($this->supportAgent())
            ->get(route('admin.reports.index', ['period' => 7]))
            ->assertOk()
            ->assertDontSee('Export CSV');
    }

    public function test_employees_cannot_see_reports(): void
    {
        $this->actingAs($this->employee())->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_unknown_periods_are_rejected(): void
    {
        $this->actingAs($this->admin())->get(route('admin.reports.index', ['period' => 9999]))->assertSessionHasErrors('period');
    }

    public function test_admins_can_export_the_periods_tickets_as_csv(): void
    {
        $ticket = Ticket::factory()->for(User::factory()->state(['name' => 'Nimal Perera']), 'creator')->create(['title' => 'Printer jammed']);
        Ticket::factory()->create(['title' => 'Too old', 'created_at' => now()->subDays(60)]);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.export', ['period' => 30]));

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith('Reference,Title,Category,Priority,Status', $csv);
        $this->assertStringContainsString("{$ticket->reference},\"Printer jammed\",", $csv);
        $this->assertStringContainsString('Nimal Perera', $csv);
        $this->assertStringNotContainsString('Too old', $csv);
    }

    public function test_exported_cells_cannot_run_as_spreadsheet_formulas(): void
    {
        Ticket::factory()->create(['title' => '=HYPERLINK("http://evil.example","click me")']);

        $csv = $this->actingAs($this->admin())->get(route('admin.reports.export'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_only_people_with_the_export_permission_can_export(): void
    {
        $this->actingAs($this->supportAgent())->get(route('admin.reports.export'))->assertForbidden();
    }
}
