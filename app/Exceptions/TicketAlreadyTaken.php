<?php

namespace App\Exceptions;

use App\Models\Ticket;
use Exception;

/**
 * Thrown when a technician tries to take a ticket someone else has already taken.
 */
class TicketAlreadyTaken extends Exception
{
    public static function for(Ticket $ticket): self
    {
        return new self("{$ticket->reference} has already been taken by another technician.");
    }
}
