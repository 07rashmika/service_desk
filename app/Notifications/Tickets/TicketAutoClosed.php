<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\User;

/**
 * Tells the requester their resolved ticket was closed because they didn't respond.
 */
class TicketAutoClosed extends TicketNotification
{
    protected function preference(): ?NotificationPreference
    {
        return NotificationPreference::TicketUpdates;
    }

    public function message(User $notifiable): string
    {
        return "{$this->ticket->reference} was closed automatically. Report a new issue if the problem comes back";
    }

    protected function icon(): string
    {
        return 'lock_clock';
    }

    protected function color(): string
    {
        return 'slate';
    }
}
