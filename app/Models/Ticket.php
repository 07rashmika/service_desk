<?php

namespace App\Models;

use App\Enums\TicketState;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'title',
    'description',
    'category_id',
    'priority_id',
    'status_id',
    'created_by',
    'assigned_to',
    'solution',
    'due_at',
    'first_response_at',
    'resolved_at',
    'closed_at',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, SoftDeletes;

    public const REFERENCE_PREFIX = 'SD-';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'assigned_to' => 'integer',
            'due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * The human-friendly ticket number shown in the UI, e.g. "SD-000042".
     *
     * @return Attribute<string, never>
     */
    protected function reference(): Attribute
    {
        return Attribute::get(fn (): string => self::REFERENCE_PREFIX.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT));
    }

    /**
     * The ticket's current step in the workflow.
     */
    public function state(): TicketState
    {
        return $this->status->slug;
    }

    /**
     * Whether the ticket is still being worked on (not resolved or closed).
     */
    public function isActive(): bool
    {
        return ! in_array($this->state(), [TicketState::Resolved, TicketState::Closed], true);
    }

    /**
     * @return BelongsTo<TicketCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<TicketPriority, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    /**
     * @return BelongsTo<TicketStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    /**
     * The employee who reported the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The technician currently working on the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * @return HasMany<TicketAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Every assignment the ticket has had, including the current one.
     *
     * @return HasMany<TicketAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }

    /**
     * Tickets reported by the given user.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function reportedBy(Builder $query, User $user): void
    {
        $query->where('created_by', $user->id);
    }

    /**
     * Tickets currently in any of the given workflow states.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inStates(Builder $query, TicketState ...$states): void
    {
        $query->whereIn('status_id', TicketStatus::query()->whereIn('slug', $states)->select('id'));
    }

    /**
     * Match a ticket number ("SD-000042" or "42") or words in the title or description.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.addcslashes($term, '\\%_').'%';

        $query->where(function (Builder $query) use ($term, $like): void {
            if (preg_match('/^(?:'.preg_quote(self::REFERENCE_PREFIX, '/').')?0*(\d+)$/i', $term, $matches)) {
                $query->orWhere('id', (int) $matches[1]);
            }

            $query->orWhere('title', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }
}
