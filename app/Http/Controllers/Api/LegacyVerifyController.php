<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VerificationRequest;
use App\Models\IncomingPaymentSms;
use App\Models\SignUp;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class LegacyVerifyController extends Controller
{
    /**
     * Legacy Verification (submit_verification_request.php)
     */
    public function submitVerificationRequest(Request $request)
    {
        $user_id = $request->input('user_id');
        $amount = $request->input('amount');
        $transaction_id = trim($request->input('transaction_id', ''));
        $payment_gateway = trim($request->input('payment_gateway', ''));
        $current_time = now()->format('d-m-Y h:i A');

        if (!$user_id || !$amount || !$transaction_id || !$payment_gateway) {
            return response()->json(['status' => 'error', 'message' => "অবৈধ ডেটা"]);
        }

        try {
            VerificationRequest::create([
                'user_id' => $user_id,
                'amount' => $amount,
                'transaction_id' => $transaction_id,
                'payment_gateway' => $payment_gateway,
                'status' => 'Pending',
                'created_at' => $current_time
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "ভেরিফিকেশন রিকোয়েস্ট সাবমিট হয়েছে। ২-২৪ ঘণ্টার মধ্যে অ্যাকাউন্ট ভেরিফাই হয়ে যাবে।"
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => "রিকোয়েস্ট সাবমিট করতে ব্যর্থ"]);
        }
    }

    /**
     * Legacy Payment SMS Hook (payment_sms_hook.php)
     */
    public function handlePaymentSmsHook(Request $request)
    {
        $secret = 'nOiNnz6Pl72tUFwxVeifvhCD0YsGzKhZRPZ9YYzxwfI=';
        $signature = $request->header('X-Signature');
        $deviceId = $request->header('X-Device-Id');
        
        $body = $request->getContent();
        $calcSig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        if (!$deviceId || $calcSig !== $signature) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $gateway = strtolower(trim($payload['gateway'] ?? ''));
        $txnId = trim($payload['transaction_id'] ?? '');
        $amount = (float)($payload['amount'] ?? 0);

        if (!$gateway || !$txnId || $amount <= 0) {
            return response()->json(['success' => false, 'message' => 'Missing fields'], 422);
        }

        IncomingPaymentSms::updateOrCreate(
            ['transaction_id' => $txnId, 'gateway' => $gateway],
            [
                'sender' => $payload['sender'] ?? '',
                'account_number' => $payload['account_number'] ?? null,
                'amount' => $amount,
                'received_at' => $payload['received_at'] ?? now(),
                'raw_text' => $payload['raw_text'] ?? '',
                'device_id' => $deviceId
            ]
        );

        $match = VerificationRequest::where('status', 'Pending')
            ->where('transaction_id', $txnId)
            ->where('amount', $amount)
            ->first();

        if ($match && $amount >= 250) {
            DB::transaction(function () use ($match, $txnId, $gateway) {
                $user = SignUp::find($match->user_id);
                $time = date("d-m-Y h:i A");
                $raw_time = now()->toDateTimeString();

                $user->update(['is_verified' => 1, 'verified_at' => $time, 'verified_raw_time' => $raw_time]);
                Notification::create(['user_id' => $user->id, 'message' => "আপনার ভেরিফিকেশন সফল হয়েছে", 'created_at' => $time]);
                $match->update(['status' => 'Approved', 'updated_at' => $time, 'verified_raw_time' => $raw_time]);
                IncomingPaymentSms::where('transaction_id', $txnId)->where('gateway', $gateway)->update(['processed' => 'Matched', 'matched_request_id' => $match->id]);
            });
            return response()->json(['success' => true, 'message' => 'Matched & Approved']);
        }

        return response()->json(['success' => true, 'message' => 'Logged']);
    }
}
