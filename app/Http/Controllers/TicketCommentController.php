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
        );

        return redirect()
            ->to(route('tickets.show', $ticket).'#comment-'.$comment->id)
            ->with('success', 'Your reply was added.');
    }
}
