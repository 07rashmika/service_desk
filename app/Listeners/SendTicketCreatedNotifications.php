<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Notifications\Tickets\TicketLoggedForYou;

class SendTicketCreatedNotifications
{
    /**
     * When IT staff log a ticket for someone, let that person know.
     */
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        if ($ticket->logged_by !== null) {
            $ticket->creator->notify(new TicketLoggedForYou($ticket));
        }
    }
}
