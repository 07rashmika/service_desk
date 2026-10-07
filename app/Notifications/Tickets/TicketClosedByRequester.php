<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\User;

/**
 * Tells the technician the requester confirmed their fix (in-app only).
 */
class TicketClosedByRequester extends TicketNotification
{
    protected function preference(): ?NotificationPreference
    {
        return null;
    }

    public function message(User $notifiable): string
    {
        return "{$this->ticket->creator->name} confirmed the fix and closed {$this->ticket->reference}";
    }

    protected function icon(): string
    {
        return 'done_all';
    }

    protected function color(): string
    {
        return 'slate';
    }
}
