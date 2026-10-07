<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\TicketState;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Loads a ticket's conversation and builds the activity timeline and workflow
 * stepper shared by the requester's and the support team's ticket pages.
 */
trait PresentsTicketActivity
{
    protected function loadTicketActivity(Ticket $ticket, bool $includeInternalNotes): void
    {
        $ticket->load([
            'status',
            'priority',
            'category',
            'creator.department',
            'loggedBy',
            'assignee',
            'attachments' => fn ($query) => $query->whereNull('ticket_comment_id'),
            'comments' => fn ($query) => $query
                ->when(! $includeInternalNotes, fn ($query) => $query->visibleToRequester())
                ->with(['user.roles', 'attachments'])
                ->oldest()
                ->orderBy('id'),
            'assignments' => fn ($query) => $query->with(['assignee', 'assigner'])->oldest()->orderBy('id'),
            'activities' => fn ($query) => $query
                // Comments appear as their own cards, and creation is the original request.
                ->whereNotIn('event', ['ticket.created', 'ticket.comment_added', 'ticket.internal_note_added'])
                ->when(! $includeInternalNotes, fn ($query) => $query->where('event', '!=', 'ticket.sla_breached'))
                ->with('user'),
        ]);
    }

    /**
     * Comments and key events in date order, for the activity feed under the original request.
     *
     * @return Collection<int, array{type: string, at: CarbonInterface, comment?: TicketComment, icon?: string, text?: string, color?: string}>
     */
    protected function timeline(Ticket $ticket): Collection
    {
        $statusColors = TicketStatus::query()->get(['name', 'color'])->mapWithKeys(fn (TicketStatus $status): array => [$status->name => $status->color->value]);

        $events = $ticket->activities->map(fn (ActivityLog $activity): array => [
            'type' => 'event',
            'at' => $activity->created_at,
            ...match ($activity->event) {
                'ticket.assigned' => ['icon' => 'person_add', 'color' => 'violet'],
                'ticket.status_changed' => ['icon' => 'sync', 'color' => $statusColors[$activity->properties['new']['status'] ?? ''] ?? 'slate'],
                'ticket.triaged' => ['icon' => 'tune', 'color' => 'blue'],
                'ticket.sla_breached' => ['icon' => 'warning', 'color' => 'red'],
                default => ['icon' => 'history', 'color' => 'slate'],
            },
            'text' => $activity->description,
        ]);

        $comments = $ticket->comments->map(fn (TicketComment $comment): array => [
            'type' => 'comment',
            'at' => $comment->created_at,
            'comment' => $comment,
        ]);

        return $events->concat($comments)->sortBy(fn (array $item): int => $item['at']->getTimestamp())->values();
    }

    /**
     * The workflow stepper: every status in order, with when the ticket reached it.
     *
     * @return array{steps: array<int, array{label: string, time: ?string}>, current: int, color: string}
     */
    protected function workflowSteps(Ticket $ticket): array
    {
        $reachedAt = [
            TicketState::Open->value => $ticket->created_at,
            TicketState::Assigned->value => $ticket->assignments->first()?->created_at,
            TicketState::InProgress->value => $ticket->first_response_at,
            TicketState::Resolved->value => $ticket->resolved_at,
            TicketState::Closed->value => $ticket->closed_at,
        ];

        $statuses = TicketStatus::query()->ordered()->get();

        return [
            'steps' => $statuses->map(fn (TicketStatus $status): array => [
                'label' => $status->name,
                'time' => ($reachedAt[$status->slug->value] ?? null)?->format('M j, g:i A'),
            ])->all(),
            'current' => $statuses->search(fn (TicketStatus $status): bool => $status->is($ticket->status)),
            'color' => $ticket->status->color->value,
        ];
    }
}
