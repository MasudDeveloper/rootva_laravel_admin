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
        // Prevent execution timeout and memory limit issues for large user lists
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'target' => 'required|in:all,specific,verified,unverified',
            'referCode' => 'required_if:target,specific',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'image_url_input' => 'nullable|url',
            'link' => 'nullable|string'
        ]);

        $title = $request->title;
        $body = $request->body;
        $target = $request->target;
        $referCode = $request->referCode;
        $link = $request->link;
        
        $image = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/notifications'), $filename);
            $image = secure_asset('uploads/notifications/' . $filename);
        } elseif ($request->filled('image_url_input')) {
            $image = $request->image_url_input;
        }

        $users = [];

        if ($target === 'all') {
            $users = SignUp::whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();
        } elseif ($target === 'specific') {
            $users = SignUp::where('referCode', $referCode)->whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();
        } elseif ($target === 'verified') {
            $users = SignUp::whereIn('is_verified', [1, 3])->whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();
        } elseif ($target === 'unverified') {
            $users = SignUp::whereNotIn('is_verified', [1, 3])->whereNotNull('fcm_token')->where('fcm_token', '!=', '')->get();
        }

        if ($users->isEmpty()) {
            return back()->with('error', 'No eligible users found with valid FCM tokens.');
        }

        $successCount = 0;
        foreach ($users as $user) {
            // 1. Save to database notification table for app history
            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'message' => $body,
                'image' => $image,
                'link' => $link,
                'created_at' => date("d-m-Y h:i A")
            ]);

            // 2. Send Push Notification via FCM Service
            try {
                $response = Http::asForm()->post('https://rootvaadmin.rootvabd.com/send_notification.php', [
                    'token' => $user->fcm_token,
                    'title' => $title,
                    'body'  => $body,
                    'image' => $image,
                    'image_url' => $image,
                    'url' => $image,
                    'link' => $link,
                    'click_action' => $link
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
