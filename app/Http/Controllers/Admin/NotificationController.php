<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Notification;
use App\Models\SavedPushNotification;
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

        $successCount = $this->sendNotificationsToUsers($users, $title, $body, $image, $link);

        return back()->with('success', "Notification sent to $successCount users successfully!");
    }

    public function savedIndex()
    {
        $savedNotifications = SavedPushNotification::orderBy('id', 'desc')->paginate(20);
        return view('admin.notifications.saved', compact('savedNotifications'));
    }

    public function saveDraft(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'image_url_input' => 'nullable|url',
            'link' => 'nullable|string'
        ]);

        $image = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/notifications'), $filename);
            $image = secure_asset('uploads/notifications/' . $filename);
        } elseif ($request->filled('image_url_input')) {
            $image = $request->image_url_input;
        }

        SavedPushNotification::create([
            'title' => $request->title,
            'body' => $request->body,
            'image' => $image,
            'link' => $request->link,
        ]);

        return back()->with('success', 'Notification template saved successfully!');
    }

    public function deleteDraft($id)
    {
        $draft = SavedPushNotification::findOrFail($id);
        $draft->delete();
        return back()->with('success', 'Saved notification deleted successfully!');
    }

    public function sendDraft(Request $request, $id)
    {
        // Prevent execution timeout and memory limit issues for large user lists
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $draft = SavedPushNotification::findOrFail($id);

        $request->validate([
            'target' => 'required|in:all,specific,verified,unverified',
            'referCode' => 'required_if:target,specific',
        ]);

        $target = $request->target;
        $referCode = $request->referCode;

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

        if (empty($users) || $users->isEmpty()) {
            return back()->with('error', 'No eligible users found with valid FCM tokens.');
        }

        $successCount = $this->sendNotificationsToUsers($users, $draft->title, $draft->body, $draft->image, $draft->link);

        return back()->with('success', "Saved notification sent to $successCount users successfully!");
    }

    private function getFcmAccessToken()
    {
        $serviceAccountFile = base_path('public/fcm-service-account.json');
        if (!file_exists($serviceAccountFile)) {
            $serviceAccountFile = '/home/syfoocuv/rootvaadmin.rootvabd.com/public/fcm-service-account.json';
        }
        
        if (!file_exists($serviceAccountFile)) {
            return null;
        }

        $json = json_decode(file_get_contents($serviceAccountFile), true);
        $header = ['alg'=>'RS256','typ'=>'JWT'];
        $now = time();
        $claim = [
            'iss' => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ];

        $header_encoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $claim_encoded = rtrim(strtr(base64_encode(json_encode($claim)), '+/', '-_'), '=');
        $signature_input = "$header_encoded.$claim_encoded";
        openssl_sign($signature_input, $signature, $json['private_key'], 'SHA256');
        $jwt = "$signature_input." . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ])
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    private function sendNotificationsToUsers($users, $title, $body, $image, $link)
    {
        $successCount = 0;
        $accessToken = $this->getFcmAccessToken();

        if ($accessToken) {
            $projectId = 'rootva-f7b1f'; 
            $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
            
            $chunks = $users->chunk(50);
            
            foreach ($chunks as $chunk) {
                $responses = Http::pool(function ($pool) use ($chunk, $url, $accessToken, $title, $body, $image, $link) {
                    return $chunk->map(function ($user) use ($pool, $url, $accessToken, $title, $body, $image, $link) {
                        $message = [
                            'token' => $user->fcm_token,
                            'data' => [
                                'title' => (string) $title,
                                'body' => (string) $body
                            ]
                        ];
                        
                        if ($image) {
                            $message['data']['image'] = (string) $image;
                            $message['data']['image_url'] = (string) $image;
                            $message['data']['url'] = (string) $image;
                        }
                        if ($link) {
                            $message['data']['link'] = (string) $link;
                            $message['data']['click_action'] = (string) $link;
                            if (!$image) {
                                $message['data']['url'] = (string) $link;
                            }
                        }
                        
                        return $pool->withToken($accessToken)->asJson()->post($url, ['message' => $message]);
                    });
                });
                
                foreach ($responses as $response) {
                    if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                        $successCount++;
                    }
                }
            }
        } else {
            foreach ($users as $user) {
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
                } catch (\Exception $e) {}
            }
        }
        return $successCount;
    }
}
