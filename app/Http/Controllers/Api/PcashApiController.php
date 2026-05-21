<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PcashSetting;
use App\Models\PcashSimOffer;
use App\Models\PcashRechargeLog;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Str;

class PcashApiController extends Controller
{
    public function getSimOffers()
    {
        $offers = PcashSimOffer::where('status', 1)->latest()->get();
        return response()->json([
            'success' => true,
            'offers' => $offers
        ]);
    }

    public function recharge(Request $request)
    {
        $userId = $request->input('user_id');
        $number = $request->input('number');
        $amount = $request->input('amount');
        $operator = $request->input('operator'); // GP, RB, AT, BL, TT, SK
        $type = $request->input('type', 1); // 1=prepaid, 2=postpaid

        if (!$userId || !$number || !$amount || !$operator) {
            return response()->json(['success' => false, 'message' => 'Missing parameters']);
        }

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        if ($user->voucher_balance < $amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient voucher balance']);
        }

        $settings = PcashSetting::first();
        if (!$settings || !$settings->api_user || !$settings->api_key) {
            return response()->json(['success' => false, 'message' => 'API is not configured']);
        }

        if (!$this->checkApiBalance($settings)) {
            return response()->json(['success' => false, 'message' => 'দুঃখিত, রিচার্জ সার্ভিস সাময়িকভাবে বন্ধ আছে।']);
        }

        // Deduct Balance
        $user->voucher_balance -= $amount;
        $user->save();

        // Create Local Transaction Record
        Transaction::create([
            'user_id' => $userId,
            'refer_id' => $user->referCode,
            'amount' => $amount,
            'type' => 'payment',
            'payment_gateway' => 'Auto ' . $operator . ' Recharge',
            'description' => 'Mobile Recharge for ' . $number,
            'update_at' => date("d-m-Y h:i A"),
            'created_at' => now(),
            'date' => now()
        ]);

        // Proceed to API Call

        $uniqid = uniqid();

        // Log the attempt
        $log = PcashRechargeLog::create([
            'user_id' => $userId,
            'api_id' => $uniqid,
            'number' => $number,
            'operator' => $operator,
            'amount' => $amount,
            'type' => $type,
            'api_status' => 'pending'
        ]);

        try {
            $headers = [
                'band-key: flexisoftwarebd',
                'refer: rootvaadmin.rootvabd.com'
            ];
            $postData = [
                'user' => $settings->api_user,
                'key' => $settings->api_key,
                'amount' => $amount,
                'number' => $number,
                'service' => $settings->default_service_code,
                'type' => $type,
                'id' => $uniqid,
                'operator' => $operator
            ];

            $res = $this->makeCurlRequest('https://pcashmoney.click/sendapi/request', $headers, $postData);
            $body = $res['body'];
            $data = json_decode($body, true);

            if (isset($data['success']) && $data['success'] == true) {
                // Initial request was accepted. Now poll status API.
                $status = 'pending';
                $statusMessage = 'Recharge request is processing';
                $gatewayTrxId = null;

                for ($i = 0; $i < 6; $i++) {
                    sleep(3);
                    try {
                        $statusHeaders = [
                            'band-key: flexisoftwarebd',
                            'refer: rootvaadmin.rootvabd.com'
                        ];
                        $statusPostData = [
                            'user' => $settings->api_user,
                            'key' => $settings->api_key,
                            'id' => $uniqid
                        ];

                        $statusRes = $this->makeCurlRequest('https://pcashmoney.click/sendapi/status', $statusHeaders, $statusPostData);
                        $statusBody = $statusRes['body'];
                        $statusData = json_decode($statusBody, true);

                        if (is_array($statusData)) {
                            // Clean spaces from keys
                            $cleanData = [];
                            foreach ($statusData as $k => $v) {
                                $cleanData[trim($k)] = $v;
                            }

                            \Illuminate\Support\Facades\Log::info("PCash Cleaned Status Response (Attempt " . ($i + 1) . "):", $cleanData);

                            $resStatus = isset($cleanData['status']) ? (string)$cleanData['status'] : '';

                            if ($resStatus === '1') {
                                $status = 'success';
                                $statusMessage = 'Recharge successful';
                                $gatewayTrxId = $cleanData['trxid'] ?? null;
                                break;
                            } elseif ($resStatus === '2') {
                                $status = 'failed';
                                $statusMessage = $cleanData['message'] ?? 'Recharge failed from gateway';
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("PCash Status Polling Exception: " . $e->getMessage());
                    }
                }

                if ($status === 'success') {
                    $log->update([
                        'api_status' => 'success',
                        'api_message' => 'Recharge successful. TrxID: ' . $gatewayTrxId
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Recharge requested successfully!'
                    ]);
                } elseif ($status === 'failed') {
                    // Refund user balance
                    $user->voucher_balance += $amount;
                    $user->save();

                    // Create transaction log for refund
                    Transaction::create([
                        'user_id' => $user->id,
                        'refer_id' => $user->referCode,
                        'amount' => $amount,
                        'type' => 'income',
                        'payment_gateway' => 'PCash Refund',
                        'description' => 'Failed Recharge Refund for ' . $number,
                        'update_at' => date("d-m-Y h:i A"),
                        'created_at' => date("d-m-Y h:i A"),
                        'date' => now()
                    ]);

                    $log->update([
                        'api_status' => 'failed',
                        'api_message' => $statusMessage
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => $statusMessage
                    ]);
                } else {
                    // Still pending/processing
                    $log->update([
                        'api_status' => 'processing',
                        'api_message' => 'Recharge remains pending after check'
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Recharge request is processing. Please check history later.'
                    ]);
                }
            } else {
                $errorMessage = $data['message'] ?? $body;
                if (empty($errorMessage)) {
                    $errorMessage = 'Unknown API Error';
                }

                \Illuminate\Support\Facades\Log::error("PCash Recharge Failed for Number: {$number}, Operator: {$operator}, Amount: {$amount}. Response: " . $errorMessage);

                $log->update([
                    'api_status' => 'failed',
                    'api_message' => $errorMessage
                ]);

                // Refund since initial request failed immediately
                $user->voucher_balance += $amount;
                $user->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => $user->referCode,
                    'amount' => $amount,
                    'type' => 'income',
                    'payment_gateway' => 'PCash Refund',
                    'description' => 'Failed Recharge Refund for ' . $number,
                    'update_at' => date("d-m-Y h:i A"),
                    'created_at' => date("d-m-Y h:i A"),
                    'date' => now()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PCash Recharge Exception for Number: {$number}, Operator: {$operator}, Amount: {$amount}. Error: " . $e->getMessage());

            $log->update([
                'api_status' => 'failed',
                'api_message' => $e->getMessage()
            ]);

            // Refund user balance
            $user->voucher_balance += $amount;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $amount,
                'type' => 'income',
                'payment_gateway' => 'Recharge Refund',
                'description' => 'Failed Recharge Refund for ' . $number,
                'update_at' => date("d-m-Y h:i A"),
                'created_at' => date("d-m-Y h:i A"),
                'date' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Network error connecting to API'
            ]);
        }
    }

    public function buySimOffer(Request $request)
    {
        $userId = $request->input('user_id');
        $offerId = $request->input('offer_id');
        $number = $request->input('number');

        if (!$userId || !$offerId || !$number) {
            return response()->json(['success' => false, 'message' => 'Missing parameters']);
        }

        $user = SignUp::find($userId);
        $offer = PcashSimOffer::find($offerId);

        if (!$user || !$offer) {
            return response()->json(['success' => false, 'message' => 'User or Offer not found']);
        }

        if ($user->voucher_balance < $offer->price) {
            return response()->json(['success' => false, 'message' => 'Insufficient voucher balance']);
        }

        $settings = PcashSetting::first();
        if (!$settings || !$settings->api_user || !$settings->api_key) {
            return response()->json(['success' => false, 'message' => 'API is not configured']);
        }

        if (!$this->checkApiBalance($settings)) {
            return response()->json(['success' => false, 'message' => 'দুঃখিত, রিচার্জ সার্ভিস সাময়িকভাবে বন্ধ আছে।']);
        }

        // Deduct Balance
        $user->voucher_balance -= $offer->price;
        $user->save();

        // Create Local Transaction Record
        Transaction::create([
            'user_id' => $userId,
            'refer_id' => $user->referCode,
            'amount' => $offer->price,
            'type' => 'payment',
            'payment_gateway' => 'PCash SIM Offer',
            'description' => 'Purchased SIM Offer: ' . $offer->title . ' for ' . $number,
            'update_at' => date("d-m-Y h:i A"),
            'created_at' => now(),
            'date' => now()
        ]);

        // API Call

        $uniqid = uniqid();

        $log = PcashRechargeLog::create([
            'user_id' => $userId,
            'api_id' => $uniqid,
            'number' => $number,
            'operator' => $offer->operator,
            'amount' => $offer->price,
            'type' => $offer->type,
            'api_status' => 'pending'
        ]);

        try {
            $headers = [
                'band-key: flexisoftwarebd',
                'refer: rootvaadmin.rootvabd.com'
            ];
            $postData = [
                'user' => $settings->api_user,
                'key' => $settings->api_key,
                'amount' => $offer->price,
                'number' => $number,
                'service' => $settings->default_service_code,
                'type' => $offer->type,
                'id' => $uniqid,
                'operator' => $offer->operator
            ];

            $res = $this->makeCurlRequest('https://pcashmoney.click/sendapi/request', $headers, $postData);
            $body = $res['body'];
            $data = json_decode($body, true);

            if (isset($data['success']) && $data['success'] == true) {
                // Initial request was accepted. Now poll status API.
                $status = 'pending';
                $statusMessage = 'SIM Offer request is processing';
                $gatewayTrxId = null;

                for ($i = 0; $i < 6; $i++) {
                    sleep(3);
                    try {
                        $statusHeaders = [
                            'band-key: flexisoftwarebd',
                            'refer: rootvaadmin.rootvabd.com'
                        ];
                        $statusPostData = [
                            'user' => $settings->api_user,
                            'key' => $settings->api_key,
                            'id' => $uniqid
                        ];

                        $statusRes = $this->makeCurlRequest('https://pcashmoney.click/sendapi/status', $statusHeaders, $statusPostData);
                        $statusBody = $statusRes['body'];
                        $statusData = json_decode($statusBody, true);

                        if (is_array($statusData)) {
                            // Clean spaces from keys
                            $cleanData = [];
                            foreach ($statusData as $k => $v) {
                                $cleanData[trim($k)] = $v;
                            }

                            \Illuminate\Support\Facades\Log::info("PCash SIM Offer Cleaned Status Response (Attempt " . ($i + 1) . "):", $cleanData);

                            $resStatus = isset($cleanData['status']) ? (string)$cleanData['status'] : '';

                            if ($resStatus === '1') {
                                $status = 'success';
                                $statusMessage = 'Offer purchased successfully';
                                $gatewayTrxId = $cleanData['trxid'] ?? null;
                                break;
                            } elseif ($resStatus === '2') {
                                $status = 'failed';
                                $statusMessage = $cleanData['message'] ?? 'SIM Offer purchase failed from gateway';
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("PCash SIM Offer Status Polling Exception: " . $e->getMessage());
                    }
                }

                if ($status === 'success') {
                    $log->update([
                        'api_status' => 'success',
                        'api_message' => 'SIM Offer purchase successful. TrxID: ' . $gatewayTrxId
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Offer purchased successfully!'
                    ]);
                } elseif ($status === 'failed') {
                    // Refund user balance
                    $user->voucher_balance += $offer->price;
                    $user->save();

                    // Create transaction log for refund
                    Transaction::create([
                        'user_id' => $user->id,
                        'refer_id' => $user->referCode,
                        'amount' => $offer->price,
                        'type' => 'income',
                        'payment_gateway' => 'PCash Refund',
                        'description' => 'Failed SIM Offer Refund: ' . $offer->title . ' for ' . $number,
                        'update_at' => date("d-m-Y h:i A"),
                        'created_at' => date("d-m-Y h:i A"),
                        'date' => now()
                    ]);

                    $log->update([
                        'api_status' => 'failed',
                        'api_message' => $statusMessage
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => $statusMessage
                    ]);
                } else {
                    // Still pending/processing
                    $log->update([
                        'api_status' => 'processing',
                        'api_message' => 'SIM Offer purchase remains pending after check'
                    ]);
                    return response()->json([
                        'success' => true,
                        'message' => 'Offer purchase request is processing. Please check history later.'
                    ]);
                }
            } else {
                $errorMessage = $data['message'] ?? $body;
                if (empty($errorMessage)) {
                    $errorMessage = 'Unknown API Error';
                }

                \Illuminate\Support\Facades\Log::error("PCash SIM Offer Purchase Failed for Number: {$number}, Offer: {$offer->title}, Amount: {$offer->price}. Response: " . $errorMessage);

                $log->update([
                    'api_status' => 'failed',
                    'api_message' => $errorMessage
                ]);

                // Refund since initial request failed immediately
                $user->voucher_balance += $offer->price;
                $user->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => $user->referCode,
                    'amount' => $offer->price,
                    'type' => 'income',
                    'payment_gateway' => 'PCash Refund',
                    'description' => 'Failed SIM Offer Refund: ' . $offer->title . ' for ' . $number,
                    'update_at' => date("d-m-Y h:i A"),
                    'created_at' => date("d-m-Y h:i A"),
                    'date' => now()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PCash SIM Offer Purchase Exception for Number: {$number}, Offer: {$offer->title}, Amount: {$offer->price}. Error: " . $e->getMessage());

            $log->update([
                'api_status' => 'failed',
                'api_message' => $e->getMessage()
            ]);

            // Refund user balance
            $user->voucher_balance += $offer->price;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $offer->price,
                'type' => 'income',
                'payment_gateway' => 'Sim Offer Refund',
                'description' => 'Failed SIM Offer Refund: ' . $offer->title . ' for ' . $number,
                'update_at' => date("d-m-Y h:i A"),
                'created_at' => date("d-m-Y h:i A"),
                'date' => now()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Network error connecting to API'
            ]);
        }
    }

    private function checkApiBalance($settings)
    {
        if (!$settings || !$settings->api_user || !$settings->api_key) {
            return false;
        }

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
                $balance = isset($data['balance']) ? (float)$data['balance'] : 0.0;
                \Illuminate\Support\Facades\Log::info("PCash API Balance Check Result: " . $balance);
                return $balance >= 50.0;
            } else {
                \Illuminate\Support\Facades\Log::warning('PCash API Balance Check returned error response: ' . $body);
                return false;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PCash API Balance Check Exception: ' . $e->getMessage());
            return false;
        }
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
