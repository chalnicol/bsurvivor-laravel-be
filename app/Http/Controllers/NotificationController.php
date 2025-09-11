<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Resources\NotificationResource;
use App\Models\User;



class NotificationController extends Controller
{

    public function get_notifications()
    {
        $user = Auth::user();

        // Get all notifications for the user
        $notifications = $user->notifications()->latest()->paginate(10);

        return NotificationResource::collection($notifications);
    }

    public function getUnreadCount()
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'count' => 0,
            ]);
        }
        $user = Auth::guard('sanctum')->user();

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'count' => $unreadCount,
        ]);
    }

    public function delete_all_notifications(Request $request)
    {

        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Authenticated user not found.'], 404);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|exists:notifications,id',
        ]);

        $toDeleteIds = $request->input('ids');

        $models = $user->notifications()->whereIn('id', $toDeleteIds)->get();

        foreach ($models as $model) {
            $model->delete();
        }
        return response()->json(['message' => 'Notifications deleted successfully.']);
    }

    public function mark_all_as_read(Request $request)
    {

        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Authenticated user not found.'], 404);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|exists:notifications,id',
        ]);

        $toMarkAsReadIds = $request->input('ids');

        $models = $user->notifications()->whereIn('id', $toMarkAsReadIds)->get();

        foreach ($models as $model) {
            $model->markAsRead();
        }
        return response()->json(['message' => 'Notifications marked as read successfully.']);
    }
}
