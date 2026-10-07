<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
use App\Events\TicketAssigned;
use App\Exceptions\TicketAlreadyTaken;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A technician takes an unassigned ticket for themselves. The row is locked so two
 * technicians clicking "Take" at the same moment can't both get it.
 */
class TakeTicket
{
    public function __construct(private TransitionTicket $transitionTicket) {}

    /**
     * @throws TicketAlreadyTaken
     */
    public function handle(Ticket $ticket, User $technician): Ticket
    {
        return DB::transaction(function () use ($ticket, $technician): Ticket {
            $locked = Ticket::query()->with('status')->lockForUpdate()->findOrFail($ticket->id);

            if ($locked->assigned_to !== null || $locked->state() !== TicketState::Open) {
                throw TicketAlreadyTaken::for($locked);
            }

            $locked->assignee()->associate($technician);
            $locked->assignments()->create([
                'assigned_to' => $technician->id,
                'assigned_by' => $technician->id,
            ]);

            $this->transitionTicket->handle($locked, TicketState::Assigned);

            TicketAssigned::dispatch($locked, $technician, $technician);

            return $locked;
        });
    }
}
