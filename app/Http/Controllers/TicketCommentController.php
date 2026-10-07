<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\AddTicketComment;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketCommentController extends Controller
{
    public function store(StoreTicketCommentRequest $request, Ticket $ticket, AddTicketComment $addComment): RedirectResponse
    {
        $comment = $addComment->handle(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $request->file('attachments', []),
            $request->boolean('internal'),
        );

        // Staff reply from the support view, requesters from their own ticket page.
        $ticketUrl = $request->user()->can('viewQueue', Ticket::class)
            ? route('support.tickets.show', $ticket)
            : route('tickets.show', $ticket);

        return redirect()
            ->to($ticketUrl.'#comment-'.$comment->id)
            ->with('success', $comment->is_internal ? 'Internal note added.' : 'Your reply was added.');
    }
}
