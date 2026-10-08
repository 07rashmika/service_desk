<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\AddTicketComment;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Resources\V1\TicketCommentResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class TicketCommentController extends Controller
{
    /**
     * Reply on a ticket, or add an internal note with "internal": true (IT staff only).
     */
    public function store(StoreTicketCommentRequest $request, Ticket $ticket, AddTicketComment $addComment): JsonResponse
    {
        $comment = $addComment->handle(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $request->file('attachments', []),
            $request->boolean('internal'),
        );

        return (new TicketCommentResource($comment->load(['user', 'attachments'])))->response()->setStatusCode(201);
    }
}
