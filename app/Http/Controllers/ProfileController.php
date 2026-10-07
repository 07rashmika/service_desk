<?php

namespace App\Http\Controllers;

use App\Enums\NotificationPreference;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Show the profile page. The forms on it submit to Fortify's
     * profile-information and password routes.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('department'),
            'preferences' => NotificationPreference::cases(),
        ]);
    }

    /**
     * Save which notification emails the person wants. Unticked boxes aren't sent,
     * so every preference is stored explicitly as on or off.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['array'],
            'email.*' => ['boolean'],
        ]);

        $request->user()->forceFill([
            'notification_preferences' => collect(NotificationPreference::cases())
                ->mapWithKeys(fn (NotificationPreference $preference): array => [
                    $preference->value => $request->boolean("email.{$preference->value}"),
                ])
                ->all(),
        ])->save();

        return redirect()->route('profile.edit')->with('status', 'notifications-updated');
    }
}
