<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sends the requester a question and pauses the ticket until they reply.
 */
class AskRequesterForInfo
{
    public function __construct(
        private AddTicketComment $addComment,
        private TransitionTicket $transitionTicket,
    ) {}

    public function handle(Ticket $ticket, User $technician, string $question): Ticket
    {
        return DB::transaction(function () use ($ticket, $technician, $question): Ticket {
            $this->addComment->handle($ticket, $technician, $question);

            return $this->transitionTicket->handle($ticket, TicketState::WaitingForUser);
        });
    }
}
