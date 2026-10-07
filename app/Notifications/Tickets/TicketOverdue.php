<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent once a ticket misses its SLA deadline: to the assigned technician and to admins.
 */
class TicketOverdue extends TicketNotification
{
    protected function preference(): ?NotificationPreference
    {
        return NotificationPreference::Assignments;
    }

    public function message(User $notifiable): string
    {
        return "{$this->ticket->reference} missed its SLA deadline: {$this->ticket->title}";
    }

    protected function icon(): string
    {
        return 'warning';
    }

    protected function color(): string
    {
        return 'red';
    }

    protected function withMailDetails(MailMessage $mail, User $notifiable): MailMessage
    {
        return $mail->line(sprintf(
            '%s priority · was due %s · %s',
            $this->ticket->priority->name,
            $this->ticket->due_at?->format('M j, g:i A'),
            $this->ticket->assignee ? "assigned to {$this->ticket->assignee->name}" : 'not assigned to anyone',
        ));
    }
}
