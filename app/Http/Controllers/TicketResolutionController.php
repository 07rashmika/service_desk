<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\AddTicketComment;
use App\Actions\Tickets\TransitionTicket;
use App\Enums\TicketState;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * The requester's answer to a resolved ticket: confirm it's fixed, or reopen it.
 */
class TicketResolutionController extends Controller
{
    public function close(Ticket $ticket, TransitionTicket $transitionTicket): RedirectResponse
    {
        Gate::authorize('close', $ticket);

        $transitionTicket->handle($ticket, TicketState::Closed);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Thanks for confirming. The ticket is now closed.');
    }

    public function reopen(Request $request, Ticket $ticket, AddTicketComment $addComment, TransitionTicket $transitionTicket): RedirectResponse
    {
        Gate::authorize('reopen', $ticket);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']], [], ['reason' => 'reason']);

        DB::transaction(function () use ($ticket, $request, $validated, $addComment, $transitionTicket): void {
            $addComment->handle($ticket, $request->user(), $validated['reason']);
            $transitionTicket->handle($ticket, TicketState::InProgress);
        });

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket reopened. We've let the technician know it isn't fixed yet.");
    }
}
