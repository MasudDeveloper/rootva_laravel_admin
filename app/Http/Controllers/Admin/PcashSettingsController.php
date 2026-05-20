<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PcashSetting;

class PcashSettingsController extends Controller
{
    public function index()
    {
        $settings = PcashSetting::first();
        if (!$settings) {
            $settings = PcashSetting::create(['api_user' => '', 'api_key' => '', 'default_service_code' => '64']);
        }
        
        $apiBalance = null;
        if ($settings->api_user && $settings->api_key) {
            try {
                $headers = [
                    'band-key: flexisoftwarebd',
                    'refer: rootvaadmin.rootvabd.com'
                ];
                $postData = [
                    'user' => $settings->api_user,
                    'key' => $settings->api_key,
                ];

                $res = $this->makeCurlRequest('https://pcashmoney.click/sendapi/balance', $headers, $postData);
                $body = $res['body'];
                $data = json_decode($body, true);

                if ($data && isset($data['success']) && $data['success'] == true) {
                    $apiBalance = $data['balance'] ?? 0;
                } else {
                    \Illuminate\Support\Facades\Log::warning('PCash API Balance Check returned error: ' . $body);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('PCash API Balance Check Exception: ' . $e->getMessage());
            }
        }

        return view('admin.pcash.settings', compact('settings', 'apiBalance'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'api_user' => 'required',
            'api_key' => 'required',
            'default_service_code' => 'required'
        ]);

        $settings = PcashSetting::first();
        if (!$settings) {
            PcashSetting::create($request->all());
        } else {
            $settings->update($request->only('api_user', 'api_key', 'default_service_code'));
        }

        return redirect()->back()->with('success', 'PCash API Settings updated successfully!');
    }

    private function makeCurlRequest($url, array $headers, array $postData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
        curl_setopt($ch, CURLOPT_REFERER, 'rootvaadmin.rootvabd.com');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Mask API Key for security in logs
        $maskedPostData = $postData;
        if (isset($maskedPostData['key'])) {
            $keyLen = strlen($maskedPostData['key']);
            if ($keyLen > 8) {
                $maskedPostData['key'] = substr($maskedPostData['key'], 0, 4) . '****' . substr($maskedPostData['key'], -4);
            } else {
                $maskedPostData['key'] = '****';
            }
        }

        \Illuminate\Support\Facades\Log::info("PCash API Request Log:", [
            'url' => $url,
            'headers' => $headers,
            'post_data' => $maskedPostData
        ]);

        if ($error) {
            \Illuminate\Support\Facades\Log::error("PCash API Connection Error:", [
                'error' => $error
            ]);
            throw new \Exception("cURL Error: " . $error);
        }

        \Illuminate\Support\Facades\Log::info("PCash API Response Log:", [
            'status_code' => $httpCode,
            'response' => $response
        ]);

        return [
            'body' => $response
        ];
    }
}
