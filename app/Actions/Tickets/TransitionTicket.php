<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
use App\Events\TicketStatusChanged;
use App\Exceptions\InvalidTicketTransition;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;

/**
 * Moves a ticket to another workflow state and keeps its timeline fields in step.
 * This is the only place ticket statuses should change.
 *
 * While a ticket waits for the requester its SLA clock is paused: when it leaves
 * that state, the deadline moves later by however long it waited.
 */
class TransitionTicket
{
    /**
     * @param  User|null  $by  Who made the change; null when the system did it (e.g. auto-close).
     *
     * @throws InvalidTicketTransition
     */
    public function handle(Ticket $ticket, TicketState $to, ?User $by = null): Ticket
    {
        $from = $ticket->state();

        if (! $from->canTransitionTo($to)) {
            throw InvalidTicketTransition::between($from, $to);
        }

        $ticket->status()->associate(TicketStatus::for($to));

        if ($from === TicketState::WaitingForUser) {
            $this->resumeSlaClock($ticket);
        }

        match ($to) {
            TicketState::WaitingForUser => $ticket->sla_paused_at = now(),
            TicketState::InProgress => $this->startOrReopen($ticket, $from),
            TicketState::Resolved => $ticket->resolved_at = now(),
            TicketState::Closed => $ticket->closed_at = now(),
            default => null,
        };

        $ticket->save();

        TicketStatusChanged::dispatch($ticket, $from, $to, $by);

        return $ticket;
    }

    protected function resumeSlaClock(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at && $ticket->due_at) {
            $ticket->due_at = $ticket->due_at->copy()->addSeconds((int) $ticket->sla_paused_at->diffInSeconds(now()));
        }

        $ticket->sla_paused_at = null;
    }

    protected function startOrReopen(Ticket $ticket, TicketState $from): void
    {
        $ticket->first_response_at ??= now();

        if ($from === TicketState::Resolved) {
            $ticket->resolved_at = null;
        }
    }
}
