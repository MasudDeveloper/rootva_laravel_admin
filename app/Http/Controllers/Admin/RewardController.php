<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\SignUp;
use App\Models\VerificationRequest;
use App\Models\WheelSpinInfo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RewardController extends Controller
{
    /**
     * Daily Bonus Section
     */
    public function dailyIndex()
    {
        $winners = Transaction::with('user')
            ->where('payment_gateway', 'Daily Bonus')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.rewards.daily', compact('winners'));
    }

    public function runDailyDistribution()
    {
        $yesterday = Carbon::yesterday();
        $start = $yesterday->startOfDay()->toDateTimeString();
        $end = $yesterday->endOfDay()->toDateTimeString();

        // Find the top referrer with >= 4 approved verifications yesterday
        $topReferrer = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select('r.id as user_id', 'r.referCode', 'r.name', DB::raw('COUNT(*) as total'))
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->groupBy('s.referredBy', 'r.id', 'r.referCode', 'r.name')
            ->having('total', '>=', 4)
            ->orderByDesc('total')
            ->first();

        if (!$topReferrer) {
            return back()->with('error', 'No user qualified for the daily bonus yesterday (minimum 4 verifications required).');
        }

        // Check if already given
        $exists = Transaction::where('user_id', $topReferrer->user_id)
            ->where('payment_gateway', 'Daily Bonus')
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if ($exists) {
            return back()->with('error', "Winner ({$topReferrer->name}) has already received today's daily bonus.");
        }

        DB::transaction(function () use ($topReferrer, $yesterday) {
            $user = SignUp::find($topReferrer->user_id);
            $amount = 100.00;

            // 1. Update Balance
            $user->increment('wallet_balance', $amount);

            // 2. Log Transaction
            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode, // Legacy logic uses winner's own referCode here sometimes
                'amount' => $amount,
                'type' => 'income',
                'payment_gateway' => 'Daily Bonus',
                'description' => "🎉 Daily Winner Bonus for {$topReferrer->total} verifications on " . $yesterday->format('Y-m-d'),
                'update_at' => now()->format('d-m-Y h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', "Daily bonus of ৳100 successfully awarded to {$topReferrer->name}!");
    }

    /**
     * Weekly Bonus Section
     */
    public function weeklyIndex()
    {
        $winners = Transaction::with('user')
            ->where('payment_gateway', 'Weekly Bonus')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.rewards.weekly', compact('winners'));
    }

    public function runWeeklyDistribution()
    {
        $lastWeek = Carbon::now()->subDays(7);
        $start = $lastWeek->startOfDay()->toDateTimeString();
        $end = Carbon::now()->endOfDay()->toDateTimeString();

        // Logic: Top referrer of the last 7 days with >= 15 verifications
        $topReferrer = DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->select('r.id as user_id', 'r.referCode', 'r.name', DB::raw('COUNT(*) as total'))
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start, $end])
            ->groupBy('s.referredBy', 'r.id', 'r.referCode', 'r.name')
            ->having('total', '>=', 15)
            ->orderByDesc('total')
            ->first();

        if (!$topReferrer) {
            return back()->with('error', 'No user qualified for the weekly bonus (minimum 15 verifications required).');
        }

        DB::transaction(function () use ($topReferrer) {
            $user = SignUp::find($topReferrer->user_id);
            $amount = 500.00; // Reward for weekly top performer

            $user->increment('wallet_balance', $amount);

            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $amount,
                'type' => 'income',
                'payment_gateway' => 'Weekly Bonus',
                'description' => "🏆 Weekly Top Referrer Bonus for {$topReferrer->total} verifications in 7 days",
                'update_at' => now()->format('d-m-Y h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', "Weekly bonus of ৳500 successfully awarded to {$topReferrer->name}!");
    }

    /**
     * Spin Bonus Section
     */
    public function spinHistory()
    {
        $spins = Transaction::with('user')
            ->where('payment_gateway', 'Spin Bonus')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.rewards.spin', compact('spins'));
    }

    /**
     * Manual 10-Level Refer Bonus Trigger
     */
    public function referBonusIndex()
    {
        return view('admin.rewards.refer_bonus');
    }

    public function distributeManualReferBonus(Request $request)
    {
        $request->validate(['refer_code' => 'required|exists:sign_up,referCode']);
        
        $referCode = $request->refer_code;
        $user = SignUp::where('referCode', $referCode)->first();
        $referredBy = $user->referredBy;
        
        if (!$referredBy) {
            return back()->with('error', 'This user was not referred by anyone.');
        }

        $levels = [76, 35, 15, 10, 6, 5, 4, 3, 2, 2];
        $currentLevel = 1;
        $count = 0;

        DB::transaction(function () use ($levels, $referredBy, $referCode, &$count) {
            $uplinerRefer = $referredBy;
            $currentLevel = 1;

            while ($currentLevel <= count($levels) && $uplinerRefer) {
                $upliner = SignUp::where('referCode', $uplinerRefer)->first();
                
                if (!$upliner) break;

                $bonus = $levels[$currentLevel - 1];
                
                // 1. Update Balance
                $upliner->increment('wallet_balance', $bonus);

                // 2. Log Transaction
                Transaction::create([
                    'user_id' => $upliner->id,
                    'refer_id' => $referCode, // Subject user's code
                    'amount' => $bonus,
                    'type' => 'commission',
                    'payment_gateway' => 'Referral Bonus',
                    'description' => "Level $currentLevel Affiliate Bonus from account verification",
                    'update_at' => now()->format('d-m-Y h:i A'),
                    'created_at' => now()->toDateTimeString(),
                ]);

                // 3. Extra Perk for Level 1
                if ($currentLevel === 1) {
                    $upliner->increment('math_game', 4);
                }

                $uplinerRefer = $upliner->referredBy;
                $currentLevel++;
                $count++;
            }
        });

        return back()->with('success', "Referral bonus successfully distributed through $count levels.");
    }
}
