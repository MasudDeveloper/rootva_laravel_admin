<?php
namespace App\Traits;

trait LegacyFCMTrait
{
    /**
     * FCM Access Token Generator
     */
    private function getFCMAccessToken($serviceAccountFile)
    {
        if (!file_exists($serviceAccountFile)) return null;
        
        $json = json_decode(file_get_contents($serviceAccountFile), true);
        if (!$json || !isset($json['client_email']) || !isset($json['private_key'])) return null;

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
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

    /**
     * Send FCM Push Notification (Data-only payload to trigger Android showNotification receiver)
     */
    public function sendFCMNotification($fcmToken, $title, $body, $image = null, $link = null)
    {
        if (empty($fcmToken)) return false;

        $possiblePaths = [
            public_path('fcm-service-account.json'),
            base_path('public/fcm-service-account.json'),
            '/home/syfoocuv/rootvaadmin.rootvabd.com/public/fcm-service-account.json',
            'c:\Users\Admin\Desktop\Rootva\Api\fcm-service-account.json'
        ];

        $jsonPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $jsonPath = $path;
                break;
            }
        }

        if (!$jsonPath) return false;

        $accessToken = $this->getFCMAccessToken($jsonPath);
        if (!$accessToken) return false;

        $projectId = 'rootva-f7b1f';
        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        // 'data' payload ensures Android app's foreground and background receiver triggers showNotification()
        $message = [
            'token' => $fcmToken,
            'data' => [
                'title' => (string)$title,
                'body' => (string)$body
            ]
        ];

        if ($image) {
            $message['data']['image'] = $image;
            $message['data']['image_url'] = $image;
            $message['data']['url'] = $image;
        }

        if ($link) {
            $message['data']['link'] = $link;
            $message['data']['click_action'] = $link;
            if (!$image) {
                $message['data']['url'] = $link;
            }
        }

        $payload = json_encode(['message' => $message]);

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
}
