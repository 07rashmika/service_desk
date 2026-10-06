<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AddTicketComment
{
    public function __construct(
        private StoreTicketAttachments $storeAttachments,
        private TransitionTicket $transitionTicket,
    ) {}

    /**
     * Add a reply (or internal note) to a ticket. When the requester answers a
     * ticket that was waiting on them, it goes back to In Progress.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function handle(Ticket $ticket, User $author, string $body, array $files = [], bool $internal = false): TicketComment
    {
        return DB::transaction(function () use ($ticket, $author, $body, $files, $internal): TicketComment {
            $comment = $ticket->comments()->create([
                'user_id' => $author->id,
                'body' => $body,
                'is_internal' => $internal,
            ]);

            $this->storeAttachments->handle($ticket, $author, $files, $comment);

            if (! $internal && $ticket->created_by === $author->id && $ticket->state() === TicketState::WaitingForUser) {
                $this->transitionTicket->handle($ticket, TicketState::InProgress);
            } else {
                $ticket->touch();
            }

            return $comment;
        });
    }
}
