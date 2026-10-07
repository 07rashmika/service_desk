<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\Ticket;
use App\Models\User;

/**
 * Tells the requester which technician is now handling their ticket (in-app only).
 */
class TicketPickedUp extends TicketNotification
{
    public function __construct(Ticket $ticket, public User $technician)
    {
        parent::__construct($ticket);
    }

    protected function preference(): ?NotificationPreference
    {
        return null;
    }

    public function message(User $notifiable): string
    {
        return "{$this->technician->name} is now handling {$this->ticket->reference}";
    }

    protected function icon(): string
    {
        return 'person_check';
    }

    protected function color(): string
    {
        return 'violet';
    }
}
