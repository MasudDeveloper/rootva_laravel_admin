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
        // ১. অ্যাপ থেকে পাঠানো ইনপুট
        $user_id = $request->input('user_id');
        $refer_id = $request->input('refer_id');
        $account_number = $request->input('account_number');
        $transaction_id = trim($request->input('transaction_id', ''));
        $amount = $request->input('amount');
        $payment_gateway = trim($request->input('payment_gateway', ''));
        
        // আপনার টেবিল অনুযায়ী টাইম ফরম্যাট
        $current_time = $request->input('current_time') ?? now()->format('Y-m-d H:i:s');
    
        // ২. ভ্যালিডেশন
        if (!$user_id || !$account_number || !$transaction_id || !$amount) {
            return response()->json(['message' => "অবৈধ ডেটা"]);
        }
    
        // ৩. ডুপ্লিকেট ট্রানজেকশন চেক
        $isTxnUsed = VerificationRequest::where('transaction_id', $transaction_id)
            ->where('status', 'Approved')
            ->exists();
    
        if ($isTxnUsed) {
            return response()->json(['message' => "⚠️ এই ট্রানজেকশন আইডি ইতোমধ্যে ব্যবহৃত হয়েছে।"]);
        }
    
        // ৪. ইউজার চেক
        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['message' => "ইউজার খুঁজে পাওয়া যায়নি"]);
        }
    
        if ($user->is_verified == 1) {
            return response()->json(['message' => "ইউজার ইতিমধ্যে ভেরিফাইড"]);
        }
    
        // ৫. ডাটা সেভ করা
        if ($user->is_verified == 0) {
            try {
                DB::beginTransaction();
    
                $user->update(['is_verified' => 2]);
    
                // গুরুত্বপূর্ণ: এখানে updated_at অবশ্যই দিতে হবে কারণ আপনার DB তে এটা NOT NULL
                VerificationRequest::create([
                    'user_id'          => $user_id,
                    'refer_id'         => $refer_id,
                    'account_number'   => $account_number,
                    'transaction_id'   => $transaction_id,
                    'amount'           => $amount,
                    'payment_gateway'  => $payment_gateway,
                    'status'           => 'Pending',
                    'created_at'       => $current_time,
                    'updated_at'       => $current_time // এটি যোগ করা হয়েছে
                ]);
    
                DB::commit();
                return response()->json(['message' => "ভেরিফিকেশন রিকোয়েস্ট সফলভাবে সাবমিট হয়েছে"]);
    
            } catch (\Exception $e) {
                DB::rollBack();
                // কলামে ডুপ্লিকেট রিকোয়েস্ট (is_verified=2 থাকা অবস্থায় আবার ট্রাই করলে)
                if (isset($e->errorInfo) && $e->errorInfo[1] == 1062) {
                    return response()->json(['message' => "আপনার একটি রিকোয়েস্ট ইতিমধ্যে পেন্ডিং আছে!"]);
                }
                return response()->json(['message' => "সার্ভার এরর: " . $e->getMessage()]);
            }
        } else {
            return response()->json(['message' => "আপনার ভেরিফিকেশন রিকোয়েস্টটি প্রক্রিয়াধীন রয়েছে"]);
        }
    }

    /**
     * Legacy Payment SMS Hook (payment_sms_hook.php)
     */
    // public function handlePaymentSmsHook(Request $request)
    // {
    //     $secret = 'nOiNnz6Pl72tUFwxVeifvhCD0YsGzKhZRPZ9YYzxwfI=';
    //     $signature = $request->header('X-Signature');
    //     $deviceId = $request->header('X-Device-Id');
        
    //     $body = $request->getContent();
    //     $calcSig = base64_encode(hash_hmac('sha256', $body, $secret, true));

    //     if (!$deviceId || $calcSig !== $signature) {
    //         return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    //     }

    //     $payload = $request->all();
    //     $gateway = strtolower(trim($payload['gateway'] ?? ''));
    //     $txnId = trim($payload['transaction_id'] ?? '');
    //     $amount = (float)($payload['amount'] ?? 0);

    //     if (!$gateway || !$txnId || $amount <= 0) {
    //         return response()->json(['success' => false, 'message' => 'Missing fields'], 422);
    //     }

    //     IncomingPaymentSms::updateOrCreate(
    //         ['transaction_id' => $txnId, 'gateway' => $gateway],
    //         [
    //             'sender' => $payload['sender'] ?? '',
    //             'account_number' => $payload['account_number'] ?? null,
    //             'amount' => $amount,
    //             'received_at' => $payload['received_at'] ?? now(),
    //             'raw_text' => $payload['raw_text'] ?? '',
    //             'device_id' => $deviceId
    //         ]
    //     );

    //     $match = VerificationRequest::where('status', 'Pending')
    //         ->where('transaction_id', $txnId)
    //         ->where('amount', $amount)
    //         ->first();

    //     if ($match && $amount >= 250) {
    //         DB::transaction(function () use ($match, $txnId, $gateway) {
    //             $user = SignUp::find($match->user_id);
    //             $time = date("d-m-Y h:i A");
    //             $raw_time = now()->toDateTimeString();

    //             $user->update(['is_verified' => 1, 'verified_at' => $time, 'verified_raw_time' => $raw_time]);
    //             Notification::create(['user_id' => $user->id, 'message' => "আপনার ভেরিফিকেশন সফল হয়েছে", 'created_at' => $time]);
    //             $match->update(['status' => 'Approved', 'updated_at' => $time, 'verified_raw_time' => $raw_time]);
    //             IncomingPaymentSms::where('transaction_id', $txnId)->where('gateway', $gateway)->update(['processed' => 'Matched', 'matched_request_id' => $match->id]);
    //         });
    //         return response()->json(['success' => true, 'message' => 'Matched & Approved']);
    //     }

    //     return response()->json(['success' => true, 'message' => 'Logged']);
    // }
}
