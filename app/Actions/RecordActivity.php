<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Adds an entry to the audit trail. Pass $user = null for things the system did itself.
 */
class RecordActivity
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function handle(?User $user, string $event, string $description, ?Model $subject = null, array $old = [], array $new = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'user_id' => $user?->id,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'description' => $description,
            'properties' => $old === [] && $new === [] ? null : ['old' => $old, 'new' => $new],
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
