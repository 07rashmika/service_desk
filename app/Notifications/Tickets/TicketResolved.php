<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Asks the requester to confirm the fix or reopen the ticket.
 */
class TicketResolved extends TicketNotification
{
    protected function preference(): ?NotificationPreference
    {
        return NotificationPreference::TicketUpdates;
    }

    public function message(User $notifiable): string
    {
        return "{$this->ticket->reference} was resolved. Please confirm the fix";
    }

    protected function icon(): string
    {
        return 'task_alt';
    }

    protected function color(): string
    {
        return 'emerald';
    }

    protected function withMailDetails(MailMessage $mail, User $notifiable): MailMessage
    {
        return $mail
            ->line("Solution: {$this->ticket->solution}")
            ->line("If it's still not working, open the ticket and choose “Not fixed – reopen”.");
    }
}
