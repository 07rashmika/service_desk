<?php

namespace App\Listeners;

use App\Enums\TicketState;
use App\Events\TicketStatusChanged;
use App\Notifications\Tickets\TicketClosedByRequester;
use App\Notifications\Tickets\TicketReopened;
use App\Notifications\Tickets\TicketResolved;

class SendTicketStatusNotifications
{
    /**
     * Only the status changes people need to act on. Questions to the requester
     * and their answers arrive as replies, so those steps send nothing here.
     */
    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket;

        match (true) {
            $event->to === TicketState::Resolved => $ticket->creator->notify(new TicketResolved($ticket)),
            $event->to === TicketState::Closed => $ticket->assignee?->notify(new TicketClosedByRequester($ticket)),
            $event->from === TicketState::Resolved && $event->to === TicketState::InProgress => $ticket->assignee?->notify(new TicketReopened($ticket)),
            default => null,
        };
    }
}
