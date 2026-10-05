<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLastLogin
{
    /**
     * Remember when the user last signed in (shown on the admin Users screen).
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $user = $event->user;

        User::withoutTimestamps(fn () => $user->forceFill(['last_login_at' => now()])->save());
    }
}
