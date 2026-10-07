<?php

namespace App\Events;

use App\Models\TicketComment;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Someone replied on a ticket or added an internal note ($comment->is_internal).
 */
class TicketCommentAdded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public TicketComment $comment) {}
}
