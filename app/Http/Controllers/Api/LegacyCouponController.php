<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Coupon;
use App\Models\CouponClaim;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class LegacyCouponController extends Controller
{
    /**
     * Get Coupon User Data, Total Income, and Claim History (get_coupon_data.php)
     */
    public function getCouponData(Request $request)
    {
        $userId = $request->input('user_id');
        $number = $request->input('number');

        $user = null;
        if ($userId) {
            $user = SignUp::find($userId);
        } elseif ($number) {
            $user = SignUp::where('number', $number)->first();
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'ইউজার পাওয়া যায়নি।'
            ], 404);
        }

        $totalIncome = (float) CouponClaim::where('user_id', $user->id)->sum('amount');
        $totalClaims = (int) CouponClaim::where('user_id', $user->id)->count();

        $history = CouponClaim::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(30)
            ->get();

        $tips = [
            "মিটিং চলাকালীন লাইভে দেওয়া স্পেশাল কুপন কোডটি দ্রুত কপি করে ক্লেইম করুন।",
            "যে আগে ক্লেইম করবে শুধুমাত্র সেই বোনাস পাবে (First-come, first-served)।",
            "প্রতিটি কুপন কোড নির্ধারিত সংখ্যক ইউজার (বা ১ জন) একবারই ক্লেইম করতে পারবেন।",
            "মিটিংয়ের সময়সূচি ও আপডেট পেতে আমাদের অফিসিয়াল টেলিগ্রাম ও হোয়াটসঅ্যাপ গ্রুপে সক্রিয় থাকুন।"
        ];

        return response()->json([
            'status' => 'success',
            'wallet_balance' => (float)$user->wallet_balance,
            'total_income' => $totalIncome,
            'total_claims' => $totalClaims,
            'history' => $history,
            'tips' => $tips
        ]);
    }

    /**
     * Claim Coupon Code (claim_coupon.php)
     */
    public function claimCoupon(Request $request)
    {
        $userId = $request->input('user_id');
        $code = strtoupper(trim($request->input('code', '')));

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'User ID is required.'
            ], 400);
        }

        if (empty($code)) {
            return response()->json([
                'status' => 'error',
                'message' => 'অনুগ্রহ করে কুপন কোডটি লিখুন।'
            ], 400);
        }

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'ইউজার পাওয়া যায়নি।'
            ], 404);
        }

        // Database transaction with lock to ensure strict first-come first-served
        try {
            return DB::transaction(function () use ($user, $code) {
                $coupon = Coupon::where('code', $code)->lockForUpdate()->first();

                if (!$coupon) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'ভুল কুপন কোড! অনুগ্রহ করে সঠিক কোড দিন।'
                    ]);
                }

                if (!$coupon->status) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'এই কুপন কোডটি বর্তমানে নিষ্ক্রিয় রয়েছে।'
                    ]);
                }

                if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'এই কুপন কোডটির মেয়াদ শেষ হয়ে গেছে।'
                    ]);
                }

                // Check if user already claimed this coupon
                $alreadyClaimed = CouponClaim::where('coupon_id', $coupon->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($alreadyClaimed) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'আপনি ইতিমধ্যে এই কুপন কোডটি ক্লেইম করেছেন!'
                    ]);
                }

                // Check if coupon limit reached (e.g. 1 user limit)
                if ($coupon->used_count >= $coupon->max_uses) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'দুঃখিত! এই কুপনটির লিমিট শেষ হয়ে গেছে। অন্য কেউ আগে ক্লেইম করে নিয়েছে।'
                    ]);
                }

                // 1. Increment used_count
                $coupon->increment('used_count', 1);

                $rewardAmount = (float) $coupon->amount;
                $now = date("Y-m-d H:i:s");
                $now2 = date("d-m-Y h:i A");

                // 2. Increment user wallet
                $user->increment('wallet_balance', $rewardAmount);

                // 3. Log Coupon Claim
                CouponClaim::create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'code' => $coupon->code,
                    'amount' => $rewardAmount,
                    'created_at' => $now2,
                    'date' => $now
                ]);

                // 4. Log Income Transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => '',
                    'amount' => $rewardAmount,
                    'type' => 'income',
                    'payment_gateway' => 'Coupon Bonus',
                    'description' => 'Meeting coupon reward: ' . $coupon->code,
                    'update_at' => $now2,
                    'created_at' => $now2,
                    'date' => $now
                ]);

                $freshUser = $user->fresh();
                $newTotalIncome = (float) CouponClaim::where('user_id', $user->id)->sum('amount');
                $newTotalClaims = (int) CouponClaim::where('user_id', $user->id)->count();

                return response()->json([
                    'status' => 'success',
                    'message' => "🎉 অভিনন্দন! আপনি ৳{$rewardAmount} কুপন বোনাস পেয়েছেন।",
                    'amount' => $rewardAmount,
                    'wallet_balance' => (float)$freshUser->wallet_balance,
                    'total_income' => $newTotalIncome,
                    'total_claims' => $newTotalClaims
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'কুপন প্রসেসিংয়ে সমস্যা হয়েছে: ' . $e->getMessage()
            ], 500);
        }
    }
}
