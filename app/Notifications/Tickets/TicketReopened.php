<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\User;

/**
 * Tells the technician the requester says their fix didn't work.
 */
class TicketReopened extends TicketNotification
{
    protected function preference(): ?NotificationPreference
    {
        return NotificationPreference::Assignments;
    }

    public function message(User $notifiable): string
    {
        return "{$this->ticket->creator->name} reopened {$this->ticket->reference}: it isn't fixed yet";
    }

    protected function icon(): string
    {
        return 'replay';
    }

    protected function color(): string
    {
        return 'orange';
    }
}
