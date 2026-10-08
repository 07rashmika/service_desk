<?php

namespace App\Http\Resources\V1;

use App\Enums\TicketState;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ticket
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'description' => $this->description,
            'status' => new LookupResource($this->whenLoaded('status')),
            'priority' => new LookupResource($this->whenLoaded('priority')),
            'category' => new LookupResource($this->whenLoaded('category')),
            'requester' => new UserSummaryResource($this->whenLoaded('creator')),
            'logged_by' => $this->whenLoaded('loggedBy', fn () => $this->loggedBy ? new UserSummaryResource($this->loggedBy) : null),
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? new UserSummaryResource($this->assignee) : null),
            'solution' => $this->solution,
            'sla' => [
                'due_at' => $this->due_at?->toIso8601String(),
                'paused' => $this->sla_paused_at !== null,
                'overdue' => $this->relationLoaded('status') && $this->isActive()
                    && $this->state() !== TicketState::WaitingForUser
                    && $this->due_at?->isPast() === true,
            ],
            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),
            'comments' => TicketCommentResource::collection($this->whenLoaded('comments')),
            // What the signed-in person may do next, so an app can show the right buttons.
            'can' => $this->when($this->relationLoaded('status'), fn (): array => [
                'comment' => $user->can('comment', $this->resource),
                'add_internal_note' => $user->can('addInternalNote', $this->resource),
                'take' => $user->can('take', $this->resource),
                'assign' => $user->can('assign', $this->resource),
                'start' => $user->can('changeStatus', [$this->resource, TicketState::InProgress]),
                'ask_requester' => $user->can('changeStatus', [$this->resource, TicketState::WaitingForUser]),
                'resolve' => $user->can('resolve', $this->resource),
                'triage' => $user->can('triage', $this->resource),
                'close' => $user->can('close', $this->resource),
                'reopen' => $user->can('reopen', $this->resource),
            ]),
            'web_url' => $this->urlFor($user),
        ];
    }
}
