<?php

namespace App\Actions\Tickets;

use App\Actions\RecordActivity;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;

/**
 * Corrects a ticket's category or priority. Changing the priority moves the SLA
 * deadline to match the new priority's resolution time.
 */
class UpdateTicketTriage
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handle(Ticket $ticket, int $categoryId, int $priorityId, ?User $by = null): Ticket
    {
        $old = ['category' => $ticket->category->name, 'priority' => $ticket->priority->name];

        if ($ticket->category_id !== $categoryId) {
            $ticket->category()->associate(TicketCategory::query()->findOrFail($categoryId));
        }

        if ($ticket->priority_id !== $priorityId) {
            $priority = TicketPriority::query()->findOrFail($priorityId);
            $ticket->priority()->associate($priority);
            $ticket->due_at = $ticket->created_at->copy()->addHours($priority->resolution_hours);
        }

        if (! $ticket->isDirty()) {
            return $ticket;
        }

        $ticket->save();

        $new = ['category' => $ticket->category->name, 'priority' => $ticket->priority->name];
        $changed = array_keys(array_diff_assoc($new, $old));

        $this->recordActivity->handle(
            $by,
            'ticket.triaged',
            ($by?->name ?? 'Someone').' changed the '.implode(' and ', $changed),
            $ticket,
            array_intersect_key($old, array_flip($changed)),
            array_intersect_key($new, array_flip($changed)),
        );

        return $ticket;
    }
}
