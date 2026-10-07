<?php

namespace App\Exceptions;

use App\Enums\TicketState;
use Exception;

/**
 * Thrown when code tries to move a ticket to a status the workflow doesn't allow.
 */
class InvalidTicketTransition extends Exception
{
    public static function between(TicketState $from, TicketState $to): self
    {
        return new self("A ticket can't move from {$from->label()} to {$to->label()}.");
    }
}
