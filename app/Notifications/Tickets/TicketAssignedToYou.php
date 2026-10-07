<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent to a technician when someone else gives them a ticket.
 */
class TicketAssignedToYou extends TicketNotification
{
    public function __construct(Ticket $ticket, public User $assignedBy, public ?string $note = null)
    {
        parent::__construct($ticket);
    }

    protected function preference(): ?NotificationPreference
    {
        return NotificationPreference::Assignments;
    }

    public function message(User $notifiable): string
    {
        return "{$this->assignedBy->name} assigned you {$this->ticket->reference}: {$this->ticket->title}";
    }

    protected function icon(): string
    {
        return 'assignment_ind';
    }

    protected function color(): string
    {
        return 'violet';
    }

    protected function withMailDetails(MailMessage $mail, User $notifiable): MailMessage
    {
        $mail->line("Priority: {$this->ticket->priority->name}. Due by {$this->ticket->due_at?->format('M j, g:i A')}.");

        return $this->note ? $mail->line("Handoff note: “{$this->note}”") : $mail;
    }
}
