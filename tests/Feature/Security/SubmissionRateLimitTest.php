<?php

namespace Tests\Feature\Security;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class SubmissionRateLimitTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
    }

    public function test_reporting_tickets_is_limited_to_ten_a_minute(): void
    {
        $employee = $this->employee();
        $form = [
            'title' => 'Printer is jammed',
            'description' => 'The second floor printer shows a paper jam error.',
            'category_id' => TicketCategory::factory()->create()->id,
            'priority_id' => TicketPriority::factory()->create()->id,
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($employee)->post(route('tickets.store'), $form)->assertRedirect();
        }

        $this->actingAs($employee)->post(route('tickets.store'), $form)->assertTooManyRequests();
        $this->assertSame(10, Ticket::query()->count());

        $this->travel(61)->seconds();
        $this->actingAs($employee)->post(route('tickets.store'), $form)->assertRedirect();
    }

    public function test_replies_are_limited_per_person(): void
    {
        $employee = $this->employee();
        $ticket = Ticket::factory()->for($employee, 'creator')->inState(TicketState::InProgress)->create();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($employee)->post(route('tickets.comments.store', $ticket), ['body' => "Update {$i}"])->assertRedirect();
        }

        $this->actingAs($employee)->post(route('tickets.comments.store', $ticket), ['body' => 'One more'])->assertTooManyRequests();

        // Someone else isn't affected.
        $agent = $this->supportAgent();
        $this->actingAs($agent)->post(route('tickets.comments.store', $ticket), ['body' => 'On it'])->assertRedirect();
    }

    public function test_the_api_limits_new_tickets_too(): void
    {
        $employee = $this->employee();
        $form = [
            'title' => 'VPN keeps dropping',
            'description' => 'The VPN disconnects every few minutes.',
            'category_id' => TicketCategory::factory()->create()->id,
            'priority_id' => TicketPriority::factory()->create()->id,
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($employee, 'sanctum')->postJson(route('api.v1.tickets.store'), $form)->assertCreated();
        }

        $this->actingAs($employee, 'sanctum')->postJson(route('api.v1.tickets.store'), $form)->assertTooManyRequests();
    }
}
