<?php

function getAccessToken($serviceAccountFile) {
    if (!file_exists($serviceAccountFile)) {
        die("Service account file not found: " . $serviceAccountFile);
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

// Data-only FCM notification sender
function sendFCMNotification($fcmToken, $title, $body, $image = null, $link = null) {
    // ⚠️ আপনার নতুন ডোমেন ফোল্ডারে fcm-service-account.json ফাইলটির সঠিক পাথ নিশ্চিত করুন
    $accessToken = getAccessToken('/home/syfoocuv/rootvaadmin.rootvabd.com/public/fcm-service-account.json');
    if(!$accessToken){
        die("Access token not generated. Check JSON path & permissions.");
    }
    
    $projectId = 'rootva-f7b1f'; // Firebase Project ID
    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

    // 'notification' কী-টি পুরোপুরি বাদ দিয়ে শুধুমাত্র 'data' পাঠানো হলো
    // এর ফলে ব্যাকগ্রাউন্ডেও আমাদের অ্যান্ড্রয়েড অ্যাপের showNotification() মেথডটি কল হবে
    $message = [
        'token' => $fcmToken,
        'data' => [
            'title' => $title,
            'body' => $body
        ]
    ];

    // নোটিফিকেশনে ব্যানার ইমেজ থাকলে
    if ($image) {
        $message['data']['image'] = $image;
        $message['data']['image_url'] = $image;
        $message['data']['url'] = $image;
    }

    // নোটিফিকেশনে অ্যাকশন/মিটিং লিংক থাকলে
    if ($link) {
        $message['data']['link'] = $link;
        $message['data']['click_action'] = $link;
        // যদি ইমেজ না থাকে, তবে url প্যারামিটার হিসেবেও লিংকটি ফরোয়ার্ড করা হলো
        if (!$image) {
            $message['data']['url'] = $link;
        }
    }

    $payload = json_encode([
        'message' => $message
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ],
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $fcmToken = $_POST['token'] ?? '';
    $title = $_POST['title'] ?? '';
    $body = $_POST['body'] ?? '';
    $image = $_POST['image'] ?? $_POST['image_url'] ?? $_POST['url'] ?? null;
    // এডমিন প্যানেল থেকে পাঠানো 'link' অথবা 'click_action' রিসিভ করুন
    $link = $_POST['link'] ?? $_POST['click_action'] ?? null;

    $response = sendFCMNotification($fcmToken, $title, $body, $image, $link);
    echo $response;
}
?>
