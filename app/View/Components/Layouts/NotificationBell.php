<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\Component;

/**
 * The bell in the top bar: unread count and the latest notifications. New ones
 * arrive live over Reverb (see notificationBell in resources/js/app.js).
 */
class NotificationBell extends Component
{
    public const LATEST = 6;

    public int $unreadCount = 0;

    /**
     * @var array<int, array{id: string, message: string, icon: string, color: string, time: string, read: bool, url: string}>
     */
    public array $latest = [];

    public function __construct()
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->unreadCount = $user->unreadNotifications()->count();
        $this->latest = $user->notifications()
            ->limit(self::LATEST)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => self::present($notification))
            ->all();
    }

    /**
     * @return array{id: string, message: string, icon: string, color: string, time: string, read: bool, url: string}
     */
    public static function present(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'message' => $notification->data['message'] ?? 'Ticket update',
            'icon' => $notification->data['icon'] ?? 'notifications',
            'color' => $notification->data['color'] ?? 'slate',
            'time' => $notification->created_at->diffForHumans(),
            'read' => $notification->read_at !== null,
            'url' => route('notifications.open', $notification->id),
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.layouts.notification-bell');
    }
}
