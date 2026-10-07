<?php

namespace App\Events;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A ticket moved from one workflow state to another. $by is null when the system
 * made the change (e.g. auto-close).
 */
class TicketStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketState $from,
        public TicketState $to,
        public ?User $by = null,
    ) {}
}
