<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VerificationRequest;
use App\Models\IncomingPaymentSms;
use App\Models\SignUp;
use App\Models\Notification;
use App\Models\Transaction;
use App\Traits\LegacyFCMTrait;
use Illuminate\Support\Facades\DB;

class LegacyVerifyController extends Controller
{
    use LegacyFCMTrait;

    /**
     * Legacy Verification (submit_verification_request.php)
     */
    public function submitVerificationRequest(Request $request)
    {
        // ১. অ্যাপ থেকে পাঠানো ইনপুট
        $user_id = $request->input('user_id');
        $refer_id = $request->input('refer_id');
        $account_number = trim((string)$request->input('account_number', ''));
        $transaction_id = trim((string)$request->input('transaction_id', ''));
        $amount = (float)$request->input('amount');
        $payment_gateway = trim((string)$request->input('payment_gateway', ''));
        
        $current_time = date('d-m-Y h:i A');
        $verified_raw_time = now()->toDateTimeString();

        // ব্যালেন্স পেমেন্ট চেক (Main Balance / Voucher Balance / ব্যালেন্স)
        $isBalancePayment = in_array(strtolower($payment_gateway), [
            'wallet balance', 'main balance', 'voucher balance', 'balance', 'ব্যালেন্স', 'wallet', 'voucher'
        ]);

        if ($isBalancePayment) {
            if (!$user_id || $amount <= 0) {
                return response()->json(['success' => false, 'status' => 'error', 'message' => "অবৈধ ডেটা"]);
            }

            $user = SignUp::find($user_id);
            if (!$user) {
                return response()->json(['success' => false, 'status' => 'error', 'message' => "ইউজার খুঁজে পাওয়া যায়নি"]);
            }

            if ($user->is_verified == 1) {
                return response()->json(['success' => false, 'status' => 'error', 'message' => "ইউজার ইতিমধ্যে ভেরিফাইড"]);
            }

            // কোন ব্যালেন্স থেকে কাটা হবে নির্ধারণ করা
            $gatewayLower = strtolower($payment_gateway);
            $deductFrom = '';

            if (in_array($gatewayLower, ['voucher balance', 'voucher'])) {
                if ((float)$user->voucher_balance < $amount) {
                    return response()->json(['success' => false, 'status' => 'error', 'message' => "আপনার ভাউচার ব্যালেন্সে পর্যাপ্ত টাকা নেই"]);
                }
                $deductFrom = 'voucher';
            } elseif (in_array($gatewayLower, ['wallet balance', 'main balance', 'wallet'])) {
                if ((float)$user->wallet_balance < $amount) {
                    return response()->json(['success' => false, 'status' => 'error', 'message' => "আপনার মেইন ব্যালেন্সে পর্যাপ্ত টাকা নেই"]);
                }
                $deductFrom = 'wallet';
            } else {
                // Generic 'balance' / 'ব্যালেন্স' -> Check voucher first, then wallet
                if ((float)$user->voucher_balance >= $amount) {
                    $deductFrom = 'voucher';
                } elseif ((float)$user->wallet_balance >= $amount) {
                    $deductFrom = 'wallet';
                } else {
                    return response()->json(['success' => false, 'status' => 'error', 'message' => "আপনার অ্যাকাউন্টে পর্যাপ্ত ব্যালেন্স নেই"]);
                }
            }

            $txnId = (!empty($transaction_id) && $transaction_id !== '0')
                ? $transaction_id
                : 'BAL' . date('ymdHis') . rand(100, 999);

            try {
                DB::beginTransaction();

                if ($deductFrom === 'voucher') {
                    $user->decrement('voucher_balance', $amount);
                    $actualGateway = 'Voucher Balance';

                    Transaction::create([
                        'user_id'         => $user->id,
                        'refer_id'        => $user->referCode ?: '',
                        'amount'          => $amount,
                        'payment_gateway' => 'Voucher Balance',
                        'type'            => 'voucher_payment',
                        'description'     => 'Verification Fee Paid from Voucher Balance',
                        'created_at'      => $current_time,
                        'update_at'       => $current_time,
                        'date'            => now()
                    ]);
                } else {
                    $user->decrement('wallet_balance', $amount);
                    $actualGateway = 'Wallet Balance';

                    Transaction::create([
                        'user_id'         => $user->id,
                        'refer_id'        => $user->referCode ?: '',
                        'amount'          => $amount,
                        'payment_gateway' => 'Wallet Balance',
                        'type'            => 'payment',
                        'description'     => 'Verification Fee Paid from Main Balance',
                        'created_at'      => $current_time,
                        'update_at'       => $current_time,
                        'date'            => now()
                    ]);
                }

                // অ্যাকাউন্ট সাথে সাথে ভেরিফাইড করা
                $user->update([
                    'is_verified'              => 1,
                    'verified_at'              => $current_time,
                    'verified_raw_time'        => $verified_raw_time,
                    'video_earning'            => $user->video_earning + 3,
                    'verification_popup_shown' => 0,
                ]);

                // Verification Request টেবিলে Approved হিসেবে রেকর্ড রাখা
                VerificationRequest::create([
                    'user_id'           => $user->id,
                    'refer_id'          => $user->referCode ?: ($refer_id ?: ''),
                    'account_number'    => $user->number ?: ($account_number ?: 'Balance'),
                    'transaction_id'    => $txnId,
                    'amount'            => $amount,
                    'payment_gateway'   => $actualGateway,
                    'status'            => 'Approved',
                    'created_at'        => $current_time,
                    'updated_at'        => $current_time,
                    'verified_raw_time' => $verified_raw_time
                ]);

                // নোটিফিকেশন তৈরি
                Notification::create([
                    'user_id'    => $user->id,
                    'message'    => '🎉 অভিনন্দন! আপনার ' . $actualGateway . ' থেকে ৳' . $amount . ' কেটে Rootva অ্যাকাউন্ট সফলভাবে ভেরিফাই করা হয়েছে।',
                    'is_read'    => 0,
                    'created_at' => $current_time,
                ]);

                if (!empty($user->fcm_token)) {
                    $this->sendFCMNotification(
                        $user->fcm_token,
                        '🎉 ভেরিফিকেশন সফল!',
                        'আপনার ' . $actualGateway . ' কেটে Rootva অ্যাকাউন্ট সফলভাবে ভেরিফাই করা হয়েছে।'
                    );
                }

                // রেফারেল বোনাস ১০ লেভেলে বিতরণ
                $this->distributeReferralBonus($user, $current_time);

                // ডিরেক্ট রেফারারের বোনাস
                if ($user->referredBy) {
                    SignUp::where('referCode', $user->referredBy)->increment('math_game', 4);
                    SignUp::where('referCode', $user->referredBy)->increment('video_earning', 3);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'status'  => 'success',
                    'message' => '🎉 অভিনন্দন! ব্যালেন্স দিয়ে আপনার অ্যাকাউন্ট সফলভাবে ভেরিফাই হয়েছে।'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'সার্ভার এরর: ' . $e->getMessage()
                ]);
            }
        }

        // ২. ম্যানুয়াল পেমেন্ট (বিকাশ, নগদ, রকেট ইত্যাদি) ভ্যালিডেশন
        if (!$user_id || empty($account_number) || empty($transaction_id) || $transaction_id === '0' || $amount <= 0) {
            return response()->json(['success' => false, 'status' => 'error', 'message' => "অবৈধ ডেটা"]);
        }
    
        // ৩. ডুপ্লিকেট ট্রানজেকশন চেক
        $isTxnUsed = VerificationRequest::where('transaction_id', $transaction_id)
            ->where('status', 'Approved')
            ->exists();
    
        if ($isTxnUsed) {
            return response()->json(['success' => false, 'status' => 'error', 'message' => "⚠️ এই ট্রানজেকশন আইডি ইতোমধ্যে ব্যবহৃত হয়েছে।"]);
        }
    
        // ৪. ইউজার চেক
        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['success' => false, 'status' => 'error', 'message' => "ইউজার খুঁজে পাওয়া যায়নি"]);
        }
    
        if ($user->is_verified == 1) {
            return response()->json(['success' => false, 'status' => 'error', 'message' => "ইউজার ইতিমধ্যে ভেরিফাইড"]);
        }
    
        // ৫. ম্যানুয়াল ডাটা সেভ করা
        if ($user->is_verified == 0) {
            try {
                DB::beginTransaction();
    
                $user->update(['is_verified' => 2]);
    
                VerificationRequest::create([
                    'user_id'          => $user_id,
                    'refer_id'         => $refer_id,
                    'account_number'   => $account_number,
                    'transaction_id'   => $transaction_id,
                    'amount'           => $amount,
                    'payment_gateway'  => $payment_gateway,
                    'status'           => 'Pending',
                    'created_at'       => $current_time,
                    'updated_at'       => $current_time
                ]);
    
                DB::commit();
                return response()->json([
                    'success' => true,
                    'status'  => 'success',
                    'message' => "ভেরিফিকেশন রিকোয়েস্ট সফলভাবে সাবমিট হয়েছে"
                ]);
    
            } catch (\Exception $e) {
                DB::rollBack();
                if ($e instanceof \Illuminate\Database\QueryException && $e->errorInfo[1] == 1062) {
                    return response()->json([
                        'success' => false,
                        'status'  => 'error',
                        'message' => "আপনার একটি রিকোয়েস্ট ইতিমধ্যে পেন্ডিং আছে!"
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => "সার্ভার এরর: " . $e->getMessage()
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => "আপনার ভেরিফিকেশন রিকোয়েস্টটি প্রক্রিয়াধীন রয়েছে"
            ]);
        }
    }

    /**
     * ১০ লেভেল রেফারেল বোনাস বিতরণ
     */
    private function distributeReferralBonus($user, $current_time)
    {
        $levels = [90, 40, 15, 10, 5, 2, 2, 2, 2, 2]; // 10 Levels
        $current_level = 1;
        $referredByCode = $user->referredBy;

        while ($current_level <= count($levels) && $referredByCode) {
            $referrer = SignUp::where('referCode', $referredByCode)->first();

            if ($referrer) {
                $bonus = $levels[$current_level - 1];

                $referrer->increment('wallet_balance', $bonus);

                Transaction::create([
                    'user_id'         => $referrer->id,
                    'refer_id'        => $user->referCode,
                    'amount'          => $bonus,
                    'type'            => 'commission',
                    'description'     => "লেভেল $current_level এফিলিয়েট বোনাস যুক্ত হয়েছে",
                    'update_at'       => $current_time,
                    'created_at'      => $current_time,
                    'payment_gateway' => 'Internal',
                    'date'            => now()
                ]);

                if (!empty($referrer->fcm_token)) {
                    $this->sendFCMNotification(
                        $referrer->fcm_token,
                        '🎁 এফিলিয়েট বোনাস যুক্ত হয়েছে!',
                        "লেভেল $current_level রেফারেল বোনাস ৳$bonus আপনার অ্যাকাউন্টে জমা হয়েছে।"
                    );
                }

                $referredByCode = $referrer->referredBy;
                $current_level++;
            } else {
                break;
            }
        }
    }
}
