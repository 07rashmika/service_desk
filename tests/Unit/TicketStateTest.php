<?php

namespace Tests\Unit;

use App\Enums\TicketState;
use PHPUnit\Framework\TestCase;

class TicketStateTest extends TestCase
{
    public function test_states_are_numbered_in_workflow_order(): void
    {
        $this->assertSame(1, TicketState::Open->sortOrder());
        $this->assertSame(4, TicketState::WaitingForUser->sortOrder());
        $this->assertSame(6, TicketState::Closed->sortOrder());
    }

    public function test_only_closed_is_final(): void
    {
        $finalStates = array_filter(TicketState::cases(), fn (TicketState $state): bool => $state->isFinal());

        $this->assertSame([TicketState::Closed], array_values($finalStates));
    }
}
