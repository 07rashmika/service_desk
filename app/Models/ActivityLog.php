<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One entry in the audit trail: who did what to which record, with before/after values.
 * Entries are only ever added, never changed.
 */
#[Fillable(['user_id', 'subject_type', 'subject_id', 'event', 'description', 'properties', 'ip_address', 'created_at'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Before/after pairs for each changed field, for the expandable diff.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function changes(): array
    {
        $old = $this->properties['old'] ?? [];
        $new = $this->properties['new'] ?? [];

        return collect(array_keys([...$old, ...$new]))
            ->mapWithKeys(fn (string $field): array => [$field => ['old' => $old[$field] ?? null, 'new' => $new[$field] ?? null]])
            ->all();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forSubject(Builder $query, Model $subject): void
    {
        $query->where('subject_type', $subject->getMorphClass())->where('subject_id', $subject->getKey());
    }
}
