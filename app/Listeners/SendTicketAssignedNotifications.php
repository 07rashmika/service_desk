<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Notifications\Tickets\TicketAssignedToYou;
use App\Notifications\Tickets\TicketPickedUp;

class SendTicketAssignedNotifications
{
    /**
     * Tell the technician (unless they took it themselves) and the requester.
     */
    public function handle(TicketAssigned $event): void
    {
        $ticket = $event->ticket;
        $note = $ticket->assignments()->latest('id')->value('note');

        if (! $event->isSelfAssigned()) {
            $event->technician->notify(new TicketAssignedToYou($ticket, $event->assignedBy, $note));
        }

        if ($ticket->created_by !== $event->technician->id) {
            $ticket->creator->notify(new TicketPickedUp($ticket, $event->technician));
        }
    }
}
