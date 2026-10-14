<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Transaction;
use App\Models\VideoEarning;
use Illuminate\Support\Facades\DB;

class LegacyVideoEarningController extends Controller
{
    /**
     * Get Video Earning Data & Active Videos (get_video_earning_data.php)
     */
    public function getVideoEarningData(Request $request)
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

        $totalIncome = Transaction::where('user_id', $user->id)
            ->where('payment_gateway', 'Video Earning')
            ->sum('amount');

        $videos = VideoEarning::where('status', 1)
            ->orderBy('serial', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($video) {
                return [
                    'id' => (int)$video->id,
                    'title' => (string)$video->title,
                    'youtube_url' => (string)$video->youtube_url,
                    'video_id' => (string)($video->video_id ?: VideoEarning::extractVideoId($video->youtube_url)),
                    'duration' => (int)($video->duration ?: 60),
                    'serial' => (int)($video->serial ?: 1),
                    'status' => 1,
                ];
            });

        $isVerified = ($user->is_verified == 1 || $user->is_verified == 3);
        $videoCount = (int)$user->video_earning;

        // Unverified user free chance guarantee:
        // If an unverified user hasn't claimed their 1 free video earning yet, ensure they have at least 1 chance
        if (!$isVerified) {
            $claimedCount = Transaction::where('user_id', $user->id)
                ->where('payment_gateway', 'Video Earning')
                ->count();
            if ($claimedCount == 0 && $videoCount < 1) {
                $videoCount = 1;
                $user->video_earning = 1;
                $user->save();
            } elseif ($claimedCount >= 1) {
                $videoCount = 0;
            }
        }

        return response()->json([
            'status' => 'success',
            'video_count' => (int)$videoCount,
            'video_income' => (float)$totalIncome,
            'wallet_balance' => (float)$user->wallet_balance,
            'videos' => $videos
        ]);
    }

    /**
     * Claim Video Watch Reward (claim_video_earning.php)
     */
    public function claimReward(Request $request)
    {
        $userId = $request->input('user_id');
        $videoId = $request->input('video_id');
        $watchTime = (int)$request->input('watch_time', 0);

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'User ID is required.'
            ], 400);
        }

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'ইউজার পাওয়া যায়নি।'
            ], 404);
        }

        // Validate minimum watch duration (minimum 58 seconds to allow slight network delay)
        if ($watchTime < 55) {
            return response()->json([
                'status' => 'error',
                'message' => 'কমপক্ষে ১ মিনিট ভিডিওটি দেখতে হবে।'
            ]);
        }

        $isVerified = ($user->is_verified == 1 || $user->is_verified == 3);

        // Check if unverified user has already enjoyed their 1 free video
        if (!$isVerified) {
            $claimedCount = Transaction::where('user_id', $user->id)
                ->where('payment_gateway', 'Video Earning')
                ->count();
            if ($claimedCount >= 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'আপনার ফ্রি ১ টি ভিডিও দেখার সুযোগ শেষ হয়েছে। আরও ভিডিও দেখতে অ্যাকাউন্ট ভেরিফাই করুন।'
                ]);
            }
        }

        // Atomic decrement to prevent race conditions
        $updated = SignUp::where('id', $userId)
            ->where('video_earning', '>', 0)
            ->decrement('video_earning', 1);

        if ($updated) {
            // Unverified users get 5 Tk for their 1 free video, verified users get 1 Tk per video
            $rewardAmount = (!$isVerified) ? 5.00 : 1.00;
            $now = date("Y-m-d H:i:s");
            $now2 = date("d-m-Y h:i A");

            // Add reward to user wallet
            $user->increment('wallet_balance', $rewardAmount);

            // Log income transaction
            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => '',
                'amount' => $rewardAmount,
                'type' => 'income',
                'payment_gateway' => 'Video Earning',
                'description' => 'Video watch reward #' . $videoId,
                'update_at' => $now2,
                'created_at' => $now2,
                'date' => $now
            ]);

            $totalIncome = Transaction::where('user_id', $user->id)
                ->where('payment_gateway', 'Video Earning')
                ->sum('amount');

            $freshUser = $user->fresh();

            $rewardFormatted = ($rewardAmount == (int)$rewardAmount) ? (int)$rewardAmount : number_format($rewardAmount, 2);

            return response()->json([
                'status' => 'success',
                'message' => "অভিনন্দন! ভিডিও দেখে {$rewardFormatted} টাকা সফলভাবে গ্রহণ করেছেন।",
                'wallet_balance' => (float)$freshUser->wallet_balance,
                'video_count' => (int)$freshUser->video_earning,
                'video_income' => (float)$totalIncome
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'আপনার ভিডিও দেখার সুযোগ শেষ। আরও সুযোগ পেতে বন্ধুদের রেফার করুন এবং ভেরিফাই করান।'
            ]);
        }
    }

    /**
     * Get Total Video Income (get_video_income.php)
     */
    public function getVideoIncome(Request $request)
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }

        $totalAmount = Transaction::where('user_id', $userId)
            ->where('payment_gateway', 'Video Earning')
            ->sum('amount');

        return response()->json([
            'video_income' => (float)$totalAmount
        ]);
    }
}
