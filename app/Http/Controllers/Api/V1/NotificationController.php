<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['unread' => ['nullable', 'boolean']]);
        $user = $request->user();

        $notifications = ($request->boolean('unread') ? $user->unreadNotifications() : $user->notifications())
            ->paginate(25)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'message' => $notification->data['message'] ?? null,
                'ticket_id' => $notification->data['ticket_id'] ?? null,
                'reference' => $notification->data['reference'] ?? null,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->toIso8601String(),
            ]);

        return response()->json([...$notifications->toArray(), 'unread_count' => $user->unreadNotifications()->count()]);
    }

    public function markRead(Request $request, string $notification): Response
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return response()->noContent();
    }

    public function markAllRead(Request $request): Response
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
