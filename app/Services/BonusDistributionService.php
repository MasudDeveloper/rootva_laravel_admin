<?php

namespace App\Services;

use App\Models\SignUp;
use App\Models\Transaction;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BonusDistributionService
{
    /**
     * Distribute Daily Tiered Bonus for a given date (defaults to yesterday in Asia/Dhaka)
     *
     * Tier structure:
     * 2 verifications => ৳20
     * 3 verifications => ৳30
     * 4 verifications => ৳40
     * 5+ verifications => ৳60
     *
     * @param Carbon|null $targetDate
     * @return array
     */
    public function distributeDailyBonus(?Carbon $targetDate = null): array
    {
        $date = $targetDate ? $targetDate->copy()->setTimezone('Asia/Dhaka') : Carbon::yesterday('Asia/Dhaka');
        $dateString = $date->format('Y-m-d');
        $start = $date->copy()->startOfDay()->toDateTimeString();
        $end = $date->copy()->endOfDay()->toDateTimeString();

        Log::info("Starting Daily Bonus distribution for date {$dateString} ({$start} to {$end}) [Asia/Dhaka]");

        // Find all referrers with >= 2 approved verifications on that day
        $referrers = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select('r.id as user_id', 'r.referCode', 'r.name', DB::raw('COUNT(*) as total'))
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->groupBy('s.referredBy', 'r.id', 'r.referCode', 'r.name')
            ->having('total', '>=', 2)
            ->get();

        if ($referrers->isEmpty()) {
            return [
                'success' => false,
                'count' => 0,
                'total_amount' => 0,
                'message' => "No users qualified for daily bonus on {$dateString} (minimum 2 verifications required)."
            ];
        }

        $count = 0;
        $totalAmount = 0;
        $nowDhaka = Carbon::now('Asia/Dhaka');

        DB::transaction(function () use ($referrers, $dateString, $nowDhaka, &$count, &$totalAmount) {
            foreach ($referrers as $referrer) {
                $total = (int) $referrer->total;
                $amount = 0;

                if ($total == 2) {
                    $amount = 20.00;
                } elseif ($total == 3) {
                    $amount = 30.00;
                } elseif ($total == 4) {
                    $amount = 40.00;
                } elseif ($total >= 5) {
                    $amount = 60.00;
                }

                if ($amount <= 0) {
                    continue;
                }

                // Duplicate prevention: check if bonus already awarded to this user for this date
                $exists = Transaction::where('user_id', $referrer->user_id)
                    ->where('payment_gateway', 'Daily Bonus')
                    ->where('description', 'like', "%{$dateString}%")
                    ->exists();

                if ($exists) {
                    continue;
                }

                $user = SignUp::find($referrer->user_id);
                if (!$user) {
                    continue;
                }

                // 1. Credit wallet
                $user->increment('wallet_balance', $amount);

                // 2. Log transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => $user->referCode,
                    'amount' => $amount,
                    'type' => 'income',
                    'payment_gateway' => 'Daily Bonus',
                    'description' => "🎉 Daily Target Bonus for {$total} verifications on {$dateString}",
                    'update_at' => $nowDhaka->format('d-m-Y h:i A'),
                    'created_at' => $nowDhaka->format('d-m-Y h:i A'),
                    'date' => $nowDhaka
                ]);

                // 3. Send notification
                Notification::create([
                    'user_id' => $user->id,
                    'message' => "অভিনন্দন! {$dateString} তারিখের ডেইলি টার্গেট ({$total} টি ভেরিফিকেশন) সফল করার জন্য আপনি ৳{$amount} বোনাস পেয়েছেন।",
                    'is_read' => 0,
                    'created_at' => $nowDhaka->format('d-m-Y h:i A')
                ]);

                $count++;
                $totalAmount += $amount;
            }
        });

        $message = "Daily target bonus successfully awarded to {$count} users for {$dateString}. Total amount: ৳{$totalAmount}!";
        Log::info($message);

        return [
            'success' => true,
            'count' => $count,
            'total_amount' => $totalAmount,
            'message' => $message
        ];
    }

    /**
     * Distribute Weekly Tiered Bonus for a completed week (Saturday 00:00:00 to Friday 23:59:59 in Asia/Dhaka)
     *
     * Tier structure:
     * 10 - 14 verifications => ৳100
     * 15 - 19 verifications => ৳150
     * 20 - 29 verifications => ৳200
     * 30+ verifications => ৳400
     *
     * @param Carbon|null $targetDateWithinWeek
     * @return array
     */
    public function distributeWeeklyBonus(?Carbon $targetDateWithinWeek = null): array
    {
        // By default, if running on Saturday morning, target date is yesterday (Friday)
        $refDate = $targetDateWithinWeek ? $targetDateWithinWeek->copy()->setTimezone('Asia/Dhaka') : Carbon::yesterday('Asia/Dhaka');
        
        // Find the Saturday-Friday cycle
        $startOfWeek = $refDate->copy()->startOfWeek(Carbon::SATURDAY)->startOfDay();
        $endOfWeek = $startOfWeek->copy()->addDays(6)->endOfDay();

        $start = $startOfWeek->toDateTimeString();
        $end = $endOfWeek->toDateTimeString();
        $weekLabel = $startOfWeek->format('Y-m-d') . " to " . $endOfWeek->format('Y-m-d');

        Log::info("Starting Weekly Bonus distribution for week {$weekLabel} ({$start} to {$end}) [Asia/Dhaka]");

        // Find all referrers with >= 10 approved verifications during this week
        $referrers = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select('r.id as user_id', 'r.referCode', 'r.name', DB::raw('COUNT(*) as total'))
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->groupBy('s.referredBy', 'r.id', 'r.referCode', 'r.name')
            ->having('total', '>=', 10)
            ->get();

        if ($referrers->isEmpty()) {
            return [
                'success' => false,
                'count' => 0,
                'total_amount' => 0,
                'message' => "No users qualified for weekly bonus for the week of {$weekLabel} (minimum 10 verifications required)."
            ];
        }

        $count = 0;
        $totalAmount = 0;
        $nowDhaka = Carbon::now('Asia/Dhaka');

        DB::transaction(function () use ($referrers, $startOfWeek, $weekLabel, $nowDhaka, &$count, &$totalAmount) {
            foreach ($referrers as $referrer) {
                $total = (int) $referrer->total;
                $amount = 0;

                if ($total >= 30) {
                    $amount = 400.00;
                } elseif ($total >= 20) {
                    $amount = 200.00;
                } elseif ($total >= 15) {
                    $amount = 150.00;
                } elseif ($total >= 10) {
                    $amount = 100.00;
                }

                if ($amount <= 0) {
                    continue;
                }

                // Duplicate prevention: check by start date in description
                $startDateStr = $startOfWeek->format('Y-m-d');
                $exists = Transaction::where('user_id', $referrer->user_id)
                    ->where('payment_gateway', 'Weekly Bonus')
                    ->where('description', 'like', "%{$startDateStr}%")
                    ->exists();

                if ($exists) {
                    continue;
                }

                $user = SignUp::find($referrer->user_id);
                if (!$user) {
                    continue;
                }

                // 1. Credit wallet
                $user->increment('wallet_balance', $amount);

                // 2. Log transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => $user->referCode,
                    'amount' => $amount,
                    'type' => 'income',
                    'payment_gateway' => 'Weekly Bonus',
                    'description' => "🎉 Weekly Target Bonus for {$total} verifications ({$weekLabel})",
                    'update_at' => $nowDhaka->format('d-m-Y h:i A'),
                    'created_at' => $nowDhaka->format('d-m-Y h:i A'),
                    'date' => $nowDhaka
                ]);

                // 3. Send notification
                Notification::create([
                    'user_id' => $user->id,
                    'message' => "অভিনন্দন! সাপ্তাহিক টার্গেট ({$total} টি ভেরিফিকেশন, {$weekLabel}) সফল করার জন্য আপনি ৳{$amount} বোনাস পেয়েছেন।",
                    'is_read' => 0,
                    'created_at' => $nowDhaka->format('d-m-Y h:i A')
                ]);

                $count++;
                $totalAmount += $amount;
            }
        });

        $message = "Weekly target bonus successfully awarded to {$count} users for week {$weekLabel}. Total amount: ৳{$totalAmount}!";
        Log::info($message);

        return [
            'success' => true,
            'count' => $count,
            'total_amount' => $totalAmount,
            'message' => $message
        ];
    }
}
