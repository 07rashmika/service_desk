<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A ticket was given to a technician. When they took it themselves,
 * $assignedBy is the technician.
 */
class TicketAssigned implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public User $technician,
        public User $assignedBy,
    ) {}

    public function isSelfAssigned(): bool
    {
        return $this->technician->is($this->assignedBy);
    }
}
