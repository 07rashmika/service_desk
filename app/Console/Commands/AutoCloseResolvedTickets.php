<?php

namespace App\Console\Commands;

use App\Actions\Tickets\TransitionTicket;
use App\Enums\TicketState;
use App\Models\Ticket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Closes resolved tickets the requester neither confirmed nor reopened in time.
 */
#[Signature('tickets:auto-close')]
#[Description('Close resolved tickets the requester has not responded to')]
class AutoCloseResolvedTickets extends Command
{
    public function handle(TransitionTicket $transitionTicket): int
    {
        $days = config('servicedesk.auto_close_after_days');
        $closed = 0;

        Ticket::query()
            ->inStates(TicketState::Resolved)
            ->where('resolved_at', '<=', now()->subDays($days))
            // Loaded up front because closing notifies the requester and the technician.
            ->with(['status', 'creator', 'assignee'])
            ->each(function (Ticket $ticket) use ($transitionTicket, &$closed): void {
                $transitionTicket->handle($ticket, TicketState::Closed);
                $closed++;
            });

        $this->components->info("Closed {$closed} ".str('ticket')->plural($closed)." resolved more than {$days} days ago.");

        return self::SUCCESS;
    }
}
