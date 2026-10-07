<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Models\TicketPriority;

/**
 * Corrects a ticket's category or priority. Changing the priority moves the SLA
 * deadline to match the new priority's resolution time.
 */
class UpdateTicketTriage
{
    public function handle(Ticket $ticket, int $categoryId, int $priorityId): Ticket
    {
        $ticket->category_id = $categoryId;

        if ($ticket->priority_id !== $priorityId) {
            $priority = TicketPriority::query()->findOrFail($priorityId);
            $ticket->priority()->associate($priority);
            $ticket->due_at = $ticket->created_at->copy()->addHours($priority->resolution_hours);
        }

        $ticket->save();

        return $ticket;
    }
}
