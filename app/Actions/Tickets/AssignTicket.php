<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
use App\Events\TicketAssigned;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hands a ticket to a technician, or passes it on to another one. An open ticket
 * becomes Assigned; a ticket already being worked on keeps its status.
 */
class AssignTicket
{
    public function __construct(private TransitionTicket $transitionTicket) {}

    public function handle(Ticket $ticket, User $technician, User $assignedBy, ?string $note = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $technician, $assignedBy, $note): Ticket {
            $locked = Ticket::query()->with('status')->lockForUpdate()->findOrFail($ticket->id);

            $locked->assignee()->associate($technician);
            $locked->assignments()->create([
                'assigned_to' => $technician->id,
                'assigned_by' => $assignedBy->id,
                'note' => $note,
            ]);

            // Announce the assignment before the status change so the history reads in order.
            TicketAssigned::dispatch($locked, $technician, $assignedBy);

            if ($locked->state() === TicketState::Open) {
                return $this->transitionTicket->handle($locked, TicketState::Assigned, $assignedBy);
            }

            $locked->save();

            return $locked;
        });
    }
}
