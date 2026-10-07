<?php

namespace App\Notifications\Tickets;

use App\Enums\NotificationPreference;
use App\Enums\TicketState;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * A new reply or internal note on a ticket the person reported or is working on.
 */
class TicketReplyAdded extends TicketNotification
{
    public function __construct(public TicketComment $comment)
    {
        parent::__construct($comment->ticket);
    }

    protected function preference(): ?NotificationPreference
    {
        return NotificationPreference::Comments;
    }

    public function message(User $notifiable): string
    {
        $author = $this->comment->user->name;
        $reference = $this->ticket->reference;

        return match (true) {
            $this->comment->is_internal => "{$author} added an internal note on {$reference}",
            $this->ticket->created_by === $notifiable->id && $this->ticket->state() === TicketState::WaitingForUser => "{$author} needs more information about {$reference}",
            default => "{$author} replied on {$reference}",
        };
    }

    protected function icon(): string
    {
        return $this->comment->is_internal ? 'lock' : 'chat';
    }

    protected function color(): string
    {
        return $this->comment->is_internal ? 'primary' : 'blue';
    }

    protected function withMailDetails(MailMessage $mail, User $notifiable): MailMessage
    {
        return $mail->line('“'.Str::limit($this->comment->body, 400).'”');
    }
}
