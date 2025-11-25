<?php

namespace App\Http\Controllers\notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Events\MainEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the current user
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $perPage = $request->get('per_page', 20);

        $notifications = Notification::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => Notification::forUser($userId)->unread()->count(),
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $userId = Auth::id();
        $count = Notification::forUser($userId)->unread()->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Get recent notifications (for dropdown)
     */
    public function recent(Request $request)
    {
        $userId = Auth::id();
        $limit = $request->get('limit', 10);

        $notifications = Notification::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => Notification::forUser($userId)->unread()->count(),
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        $userId = Auth::id();

        $notification = Notification::forUser($userId)->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification,
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $userId = Auth::id();

        Notification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $userId = Auth::id();

        $notification = Notification::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhereNull('user_id');
        })->findOrFail($id);

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Clear all read notifications
     */
    public function clearRead()
    {
        $userId = Auth::id();

        Notification::forUser($userId)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Read notifications cleared',
        ]);
    }

    /**
     * Create a new notification (admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'type' => 'required|string|in:info,warning,success,error,report,computer,access_request',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'icon' => 'nullable|string',
            'link' => 'nullable|string',
            'data' => 'nullable|array',
        ]);

        $notification = Notification::create($validated);

        // Broadcast the notification via WebSocket
        broadcast(new MainEvent('Notification', 'created', $notification))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Notification created',
            'data' => $notification,
        ], 201);
    }
}
