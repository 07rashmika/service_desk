<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
use App\Events\TicketStatusChanged;
use App\Exceptions\InvalidTicketTransition;
use App\Models\Ticket;
use App\Models\TicketStatus;

/**
 * Moves a ticket to another workflow state and keeps its timeline fields in step.
 * This is the only place ticket statuses should change.
 */
class TransitionTicket
{
    /**
     * @throws InvalidTicketTransition
     */
    public function handle(Ticket $ticket, TicketState $to): Ticket
    {
        $from = $ticket->state();

        if (! $from->canTransitionTo($to)) {
            throw InvalidTicketTransition::between($from, $to);
        }

        $ticket->status()->associate(TicketStatus::for($to));

        match ($to) {
            TicketState::InProgress => $this->startOrReopen($ticket, $from),
            TicketState::Resolved => $ticket->resolved_at = now(),
            TicketState::Closed => $ticket->closed_at = now(),
            default => null,
        };

        $ticket->save();

        TicketStatusChanged::dispatch($ticket, $from, $to);

        return $ticket;
    }

    protected function startOrReopen(Ticket $ticket, TicketState $from): void
    {
        $ticket->first_response_at ??= now();

        if ($from === TicketState::Resolved) {
            $ticket->resolved_at = null;
        }
    }
}
