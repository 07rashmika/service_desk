<?php

namespace Tests\Feature\Security;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

class OutputEscapingTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->setUpTicketReferenceData();
    }

    public function test_html_typed_into_tickets_and_replies_is_shown_as_text(): void
    {
        $employee = $this->employee();
        $ticket = Ticket::factory()->for($employee, 'creator')->inState(TicketState::InProgress)->create([
            'title' => '<b>Bold title</b>',
            'description' => '<script>alert("description")</script>',
        ]);
        TicketComment::factory()->for($ticket)->for($employee)->create(['body' => '<img src=x onerror=alert(1)>']);

        foreach ([[$employee, route('tickets.show', $ticket)], [$this->supportAgent(), route('support.tickets.show', $ticket)]] as [$user, $url]) {
            $this->actingAs($user)->get($url)
                ->assertOk()
                ->assertDontSee('<script>alert("description")</script>', false)
                ->assertDontSee('<img src=x onerror=alert(1)>', false)
                ->assertDontSee('<b>Bold title</b>', false)
                ->assertSee('&lt;script&gt;', false)
                ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
        }
    }
}
