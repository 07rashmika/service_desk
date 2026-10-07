<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\User;
use App\View\Components\Ui\SlaTimer;

/**
 * Warns the assigned technician shortly before the SLA deadline.
 */
class TicketDueSoon extends TicketNotification
{
    protected function preference(): ?NotificationPreference
    {
        return NotificationPreference::Assignments;
    }

    public function message(User $notifiable): string
    {
        $minutesLeft = max(0, (int) now()->diffInMinutes($this->ticket->due_at));

        return "{$this->ticket->reference} is due in ".SlaTimer::formatMinutes($minutesLeft).": {$this->ticket->title}";
    }

    protected function icon(): string
    {
        return 'alarm';
    }

    protected function color(): string
    {
        return 'amber';
    }
}
