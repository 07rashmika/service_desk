<?php

namespace Tests\Feature\Models;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_notes_are_hidden_from_the_requester(): void
    {
        $ticket = Ticket::factory()->create();
        $reply = TicketComment::factory()->for($ticket)->create();
        TicketComment::factory()->internal()->for($ticket)->create();

        $visible = $ticket->comments()->visibleToRequester()->get();

        $this->assertCount(1, $visible);
        $this->assertTrue($visible->first()->is($reply));
    }
}
