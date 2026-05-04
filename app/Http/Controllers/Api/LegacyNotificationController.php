<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\SignUp;

class LegacyNotificationController extends Controller
{
    /**
     * Legacy Notifications (get_notifications.php)
     */
    public function getNotifications(Request $request)
    {
        $userId = $request->query('user_id');
        if (!$userId) return response()->json([]);

        $notifications = Notification::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($notifications);
    }

    /**
     * Mark Notifications Read (mark_notifications_read.php)
     */
    public function markNotificationsAsRead(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId) return response()->json(['success' => false]);

        Notification::where('user_id', $userId)->update(['is_read' => 1]);
        
        return response()->json(['success' => true]);
    }
}
