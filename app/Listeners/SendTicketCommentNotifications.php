<?php

namespace App\Listeners;

use App\Events\TicketCommentAdded;
use App\Models\User;
use App\Notifications\Tickets\TicketReplyAdded;

class SendTicketCommentNotifications
{
    /**
     * Replies go to the other side of the conversation: the requester and the
     * assigned technician, never the author. Internal notes only go to the technician.
     */
    public function handle(TicketCommentAdded $event): void
    {
        $comment = $event->comment;
        $ticket = $comment->ticket;

        $recipients = collect([
            $comment->is_internal ? null : $ticket->creator,
            $ticket->assignee,
        ])
            ->filter()
            ->reject(fn (User $user): bool => $user->id === $comment->user_id)
            ->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new TicketReplyAdded($comment));
        }
    }
}
