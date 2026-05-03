<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::orderBy('id', 'desc')->paginate(20);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'target' => 'required|in:all,specific',
            'referCode' => 'required_if:target,specific'
        ]);

        $title = $request->title;
        $body = $request->body;
        $target = $request->target;
        $referCode = $request->referCode;

        $tokens = [];
        $users = [];

        if ($target === 'all') {
            $users = SignUp::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();
        } else {
            $users = SignUp::where('referCode', $referCode)->whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();
        }

        if ($users->isEmpty()) {
            return back()->with('error', 'No eligible users found with valid FCM tokens.');
        }

        $successCount = 0;
        foreach ($users as $user) {
            // 1. Save to database notification table for app history
            Notification::create([
                'user_id' => $user->id,
                'message' => "[$title] $body",
                'created_at' => date("d-m-Y h:i A")
            ]);

            // 2. Send Push Notification via FCM Service
            // Note: Replicating legacy behavior of calling external api.rootvabd.com/send_notification.php
            try {
                $response = Http::asForm()->post('https://api.rootvabd.com/send_notification.php', [
                    'token' => $user->fcm_token,
                    'title' => $title,
                    'body'  => $body
                ]);

                if ($response->successful()) {
                    $successCount++;
                }
            } catch (\Exception $e) {
                // Log or ignore
            }
        }

        return back()->with('success', "Notification sent to $successCount users successfully!");
    }
}
