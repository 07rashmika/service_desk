<?php

namespace App\Listeners;

use App\Actions\RecordActivity;
use App\Enums\TicketState;
use App\Events\TicketAssigned;
use App\Events\TicketCommentAdded;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Models\TicketStatus;

/**
 * Writes ticket events to the audit trail (activity_logs). Each handle method is
 * discovered automatically from its event type.
 */
class RecordTicketActivity
{
    public function __construct(private RecordActivity $recordActivity) {}

    public function handleCreated(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        $description = $ticket->loggedBy
            ? "{$ticket->loggedBy->name} logged the ticket for {$ticket->creator->name}"
            : "{$ticket->creator->name} reported the ticket";

        $this->recordActivity->handle($ticket->loggedBy ?? $ticket->creator, 'ticket.created', $description, $ticket, new: [
            'priority' => $ticket->priority->name,
            'category' => $ticket->category->name,
        ]);
    }

    public function handleAssigned(TicketAssigned $event): void
    {
        $description = $event->isSelfAssigned()
            ? "{$event->technician->name} took the ticket"
            : "{$event->assignedBy->name} assigned the ticket to {$event->technician->name}";

        $this->recordActivity->handle($event->assignedBy, 'ticket.assigned', $description, $event->ticket, new: [
            'assignee' => $event->technician->name,
        ]);
    }

    public function handleStatusChanged(TicketStatusChanged $event): void
    {
        $names = TicketStatus::query()->pluck('name', 'slug');
        $from = $names[$event->from->value] ?? $event->from->label();
        $to = $names[$event->to->value] ?? $event->to->label();

        $description = match (true) {
            $event->by === null && $event->to === TicketState::Closed => 'Closed automatically after '.config('servicedesk.auto_close_after_days').' days with no response',
            $event->by === null => "Status changed from {$from} to {$to}",
            default => "{$event->by->name} changed the status from {$from} to {$to}",
        };

        if ($event->to === TicketState::WaitingForUser) {
            $description .= ' (SLA paused)';
        } elseif ($event->from === TicketState::WaitingForUser) {
            $description .= ' (SLA resumed)';
        }

        $this->recordActivity->handle($event->by, 'ticket.status_changed', $description, $event->ticket, ['status' => $from], ['status' => $to]);
    }

    public function handleCommentAdded(TicketCommentAdded $event): void
    {
        $comment = $event->comment;
        $author = $comment->user->name;

        $this->recordActivity->handle(
            $comment->user,
            $comment->is_internal ? 'ticket.internal_note_added' : 'ticket.comment_added',
            $comment->is_internal ? "{$author} added an internal note" : "{$author} replied",
            $comment->ticket,
        );
    }
}
