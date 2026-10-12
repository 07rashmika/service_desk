<?php

namespace App\Models\Builders;

use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ticket queries. Every Ticket::query(), and every ticket relationship query
 * (e.g. inside withCount), is one of these, so the filters below can be chained
 * and type-hinted anywhere.
 *
 * @extends Builder<Ticket>
 */
class TicketBuilder extends Builder
{
    /**
     * Tickets reported by the given user.
     */
    public function reportedBy(User $user): static
    {
        return $this->where('created_by', $user->id);
    }

    /**
     * Tickets currently in any of the given workflow states.
     */
    public function inStates(TicketState ...$states): static
    {
        return $this->whereIn('status_id', TicketStatus::query()->whereIn('slug', $states)->select('id'));
    }

    /**
     * Match a ticket number ("SD-000042" or "42") or words in the title or description.
     */
    public function search(?string $term): static
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $this;
        }

        $like = '%'.addcslashes($term, '\\%_').'%';

        return $this->where(function (Builder $query) use ($term, $like): void {
            if (preg_match('/^(?:'.preg_quote(Ticket::REFERENCE_PREFIX, '/').')?0*(\d+)$/i', $term, $matches)) {
                $query->orWhere('id', (int) $matches[1]);
            }

            $query->orWhere('title', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    /**
     * Active tickets past their SLA deadline. Tickets waiting on the requester are
     * left out because their SLA timer is shown as paused.
     */
    public function overdue(): static
    {
        return $this->inStates(TicketState::Open, TicketState::Assigned, TicketState::InProgress)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }
}
