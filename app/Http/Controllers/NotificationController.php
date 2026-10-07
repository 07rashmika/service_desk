<?php

namespace App\Http\Controllers;

use App\View\Components\Layouts\NotificationBell;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $showUnread = $request->query('tab') === 'unread';

        $notifications = ($showUnread ? $user->unreadNotifications() : $user->notifications())
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'groups' => $notifications->getCollection()->groupBy(fn (DatabaseNotification $notification): string => match (true) {
                $notification->created_at->isToday() => 'Today',
                $notification->created_at->isYesterday() => 'Yesterday',
                default => 'Earlier',
            }),
            'showUnread' => $showUnread,
            'unreadCount' => $user->unreadNotifications()->count(),
            'totalCount' => $user->notifications()->count(),
            'present' => NotificationBell::present(...),
        ]);
    }

    /**
     * Mark the notification as read and go to the ticket it's about.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        return redirect()->to($notification->data['url'] ?? route('notifications.index'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
