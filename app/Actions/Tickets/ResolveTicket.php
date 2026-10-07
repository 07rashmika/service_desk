<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;

/**
 * Marks a ticket resolved with the solution the requester will see.
 */
class ResolveTicket
{
    public function __construct(private TransitionTicket $transitionTicket) {}

    public function handle(Ticket $ticket, string $solution, ?User $by = null): Ticket
    {
        $ticket->solution = $solution;

        return $this->transitionTicket->handle($ticket, TicketState::Resolved, $by);
    }
}
