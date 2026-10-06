<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Saves uploaded files to the private disk and records them against a ticket,
 * optionally as part of a comment.
 */
class StoreTicketAttachments
{
    public const DISK = 'local';

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function handle(Ticket $ticket, User $uploader, array $files, ?TicketComment $comment = null): void
    {
        foreach ($files as $file) {
            $ticket->attachments()->create([
                'ticket_comment_id' => $comment?->id,
                'user_id' => $uploader->id,
                'original_name' => $file->getClientOriginalName(),
                'disk' => self::DISK,
                'path' => $file->store("tickets/{$ticket->id}", self::DISK),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }
    }
}
