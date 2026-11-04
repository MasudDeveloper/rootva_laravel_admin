<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Notification;
use App\Models\SavedPushNotification;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::orderBy('id', 'desc')->paginate(20);
        return view('admin.notifications.index', compact('notifications'));
    }

    private function toBengaliDigits($str)
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        return str_replace($en, $bn, (string)$str);
    }

    private function getTargetUsersQuery(Request $request)
    {
        $target = $request->input('target', 'all');
        $referCode = $request->input('referCode');
        $dateFilter = $request->input('date_filter', 'all');
        $customDate = $request->input('custom_date');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = SignUp::whereNotNull('fcm_token')->where('fcm_token', '!=', '');

        if ($target === 'specific') {
            return $query->where('referCode', $referCode);
        }

        if ($target === 'verified') {
            $query->whereIn('is_verified', [1, 3]);

            if ($dateFilter && $dateFilter !== 'all') {
                $this->applyDateFilterToQuery($query, $dateFilter, $customDate, $dateFrom, $dateTo, 'verified');
            }
            return $query;
        }

        if ($target === 'unverified') {
            $query->whereNotIn('is_verified', [1, 3]);

            if ($dateFilter && $dateFilter !== 'all') {
                $this->applyDateFilterToQuery($query, $dateFilter, $customDate, $dateFrom, $dateTo, 'registration');
            }
            return $query;
        }

        if ($target === 'registered') {
            if ($dateFilter && $dateFilter !== 'all') {
                $this->applyDateFilterToQuery($query, $dateFilter, $customDate, $dateFrom, $dateTo, 'registration');
            }
            return $query;
        }

        // target === 'all'
        if ($dateFilter && $dateFilter !== 'all') {
            $this->applyDateFilterToQuery($query, $dateFilter, $customDate, $dateFrom, $dateTo, 'registration');
        }

        return $query;
    }

    private function applyDateFilterToQuery($query, $filterType, $customDate, $dateFrom, $dateTo, $mode = 'verified')
    {
        $today = Carbon::now('Asia/Dhaka')->toDateString();
        $yesterday = Carbon::yesterday('Asia/Dhaka')->toDateString();

        if ($filterType === 'today') {
            $this->applySingleDate($query, $today, $mode);
        } elseif ($filterType === 'yesterday') {
            $this->applySingleDate($query, $yesterday, $mode);
        } elseif ($filterType === 'custom' && !empty($customDate)) {
            $this->applySingleDate($query, $customDate, $mode);
        } elseif ($filterType === 'range' && !empty($dateFrom) && !empty($dateTo)) {
            $this->applyDateRange($query, $dateFrom, $dateTo, $mode);
        }
    }

    private function applySingleDate($query, $date, $mode)
    {
        $carbon = Carbon::parse($date);
        $dmyEn = $carbon->format('d-m-Y');
        $dmyBn = $this->toBengaliDigits($dmyEn);

        if ($mode === 'verified') {
            $query->where(function ($q) use ($date, $dmyEn, $dmyBn) {
                $q->whereDate('verified_raw_time', $date)
                  ->orWhere('verified_at', 'LIKE', "{$dmyEn}%")
                  ->orWhere('verified_at', 'LIKE', "{$dmyBn}%");
            });
        } else {
            $query->where(function ($q) use ($date, $dmyEn, $dmyBn) {
                $q->where('created_at', 'LIKE', "{$dmyEn}%")
                  ->orWhere('created_at', 'LIKE', "{$dmyBn}%")
                  ->orWhere('created_at', 'LIKE', "{$date}%");
            });
        }
    }

    private function applyDateRange($query, $dateFrom, $dateTo, $mode)
    {
        $fromCarbon = Carbon::parse($dateFrom)->startOfDay();
        $toCarbon = Carbon::parse($dateTo)->endOfDay();

        if ($fromCarbon->gt($toCarbon)) {
            $temp = $fromCarbon;
            $fromCarbon = $toCarbon->copy()->startOfDay();
            $toCarbon = $temp->copy()->endOfDay();
        }

        $fromStr = $fromCarbon->toDateTimeString();
        $toStr = $toCarbon->toDateTimeString();

        if ($mode === 'verified') {
            $query->where(function ($q) use ($fromStr, $toStr, $fromCarbon, $toCarbon) {
                $q->whereBetween('verified_raw_time', [$fromStr, $toStr]);
                $diffDays = $fromCarbon->diffInDays($toCarbon);
                if ($diffDays <= 31) {
                    $cursor = $fromCarbon->copy();
                    while ($cursor->lte($toCarbon)) {
                        $dmyEn = $cursor->format('d-m-Y');
                        $dmyBn = $this->toBengaliDigits($dmyEn);
                        $q->orWhere('verified_at', 'LIKE', "{$dmyEn}%")
                          ->orWhere('verified_at', 'LIKE', "{$dmyBn}%");
                        $cursor->addDay();
                    }
                }
            });
        } else {
            $query->where(function ($q) use ($fromStr, $toStr, $fromCarbon, $toCarbon) {
                $diffDays = $fromCarbon->diffInDays($toCarbon);
                if ($diffDays <= 31) {
                    $cursor = $fromCarbon->copy();
                    while ($cursor->lte($toCarbon)) {
                        $dmyEn = $cursor->format('d-m-Y');
                        $dmyBn = $this->toBengaliDigits($dmyEn);
                        $dayStr = $cursor->format('Y-m-d');
                        $q->orWhere('created_at', 'LIKE', "{$dmyEn}%")
                          ->orWhere('created_at', 'LIKE', "{$dmyBn}%")
                          ->orWhere('created_at', 'LIKE', "{$dayStr}%");
                        $cursor->addDay();
                    }
                } else {
                    $q->whereBetween('created_at', [$fromStr, $toStr]);
                }
            });
        }
    }

    public function getTargetCount(Request $request)
    {
        try {
            $count = $this->getTargetUsersQuery($request)->count();
            return response()->json([
                'success' => true,
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function send(Request $request)
    {
        // Prevent execution timeout and memory limit issues for large user lists
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'target' => 'required|in:all,specific,verified,unverified,registered',
            'referCode' => 'required_if:target,specific',
            'date_filter' => 'nullable|in:all,today,yesterday,custom,range',
            'custom_date' => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'image_url_input' => 'nullable|url',
            'link' => 'nullable|string'
        ]);

        $title = $request->title;
        $body = $request->body;
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

        $users = $this->getTargetUsersQuery($request)->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'No eligible users found with valid FCM tokens for the selected criteria.');
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
            'target' => 'required|in:all,specific,verified,unverified,registered',
            'referCode' => 'required_if:target,specific',
            'date_filter' => 'nullable|in:all,today,yesterday,custom,range',
            'custom_date' => 'nullable|date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $users = $this->getTargetUsersQuery($request)->get();

        if (empty($users) || $users->isEmpty()) {
            return back()->with('error', 'No eligible users found with valid FCM tokens for the selected criteria.');
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
