<?php

namespace App\Domains\Notifications\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Notifications\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    /**
     * Retrieve list of notifications for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        $notifications = UserNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $unreadCount = UserNotification::where('user_id', $userId)
            ->unread()
            ->count();

        return response()->json([
            'data'         => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $userId = $request->user()->user_id;

        $notification = UserNotification::where('user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        $unreadCount = UserNotification::where('user_id', $userId)
            ->unread()
            ->count();

        return response()->json([
            'message'      => 'Notification marked as read',
            'data'         => $notification,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark all notifications as read for authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;

        UserNotification::where('user_id', $userId)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message'      => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }
}
