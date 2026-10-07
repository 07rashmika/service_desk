<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\User;

/**
 * Tells an employee that IT staff logged a ticket on their behalf.
 */
class TicketLoggedForYou extends TicketNotification
{
    protected function preference(): ?NotificationPreference
    {
        return NotificationPreference::TicketUpdates;
    }

    public function message(User $notifiable): string
    {
        return "{$this->ticket->loggedBy->name} logged {$this->ticket->reference} for you: {$this->ticket->title}";
    }

    protected function icon(): string
    {
        return 'support_agent';
    }

    protected function color(): string
    {
        return 'primary';
    }
}
