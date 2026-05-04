<?php
namespace App\Traits;

trait LegacyFCMTrait
{
    /**
     * FCM Helpers
     */
    private function getFCMAccessToken($serviceAccountFile)
    {
        if (!file_exists($serviceAccountFile)) return null;
        
        $json = json_decode(file_get_contents($serviceAccountFile), true);
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

    private function sendFCMNotification($fcmToken, $title, $body)
    {
        $jsonPath = 'c:\Users\Admin\Desktop\Rootva\Api\fcm-service-account.json';
        $accessToken = $this->getFCMAccessToken($jsonPath);
        if (!$accessToken) return false;

        $projectId = 'rootva-f7b1f';
        $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";

        $payload = json_encode([
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ]
            ]
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
}
