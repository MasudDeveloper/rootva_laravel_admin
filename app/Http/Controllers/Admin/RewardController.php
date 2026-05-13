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
            ->orderBy('id', 'desc')
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
                'created_at' => now()->format('d-m-Y h:i A'),
                'date' => now()
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
            ->orderBy('id', 'desc')
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

        // Check if weekly bonus already distributed in the last 7 days
        $exists = Transaction::where('payment_gateway', 'Weekly Bonus')
            ->where('date', '>=', Carbon::now()->subDays(7))
            ->exists();

        if ($exists) {
            return back()->with('error', 'Weekly bonus has already been distributed for this week.');
        }

        DB::transaction(function () use ($topReferrer) {
            $user = SignUp::find($topReferrer->user_id);
            $amount = 1000.00; // Reward for weekly top performer
            $user->increment('wallet_balance', $amount);

            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $amount,
                'type' => 'income',
                'payment_gateway' => 'Weekly Bonus',
                'description' => "🏆 Weekly Top Referrer Bonus for {$topReferrer->total} verifications in 7 days",
                'update_at' => now()->format('d-m-Y h:i A'),
                'created_at' => now()->format('d-m-Y h:i A'),
                'date' => now()
            ]);
        });

        return back()->with('success', "Weekly bonus of ৳1000 successfully awarded to {$topReferrer->name}!");
    }

    /**
     * Spin Bonus Section
     */
    public function spinHistory()
    {
        $spins = Transaction::with('user')
            ->where('payment_gateway', 'Spin Bonus')
            ->orderBy('id', 'desc')
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
        $request->validate([
            'refer_code' => 'required|exists:sign_up,referCode',
            'selected_levels' => 'nullable|array',
            'selected_levels.*' => 'integer|min:1|max:10'
        ]);
        
        $referCode = $request->refer_code;
        $selectedLevels = $request->selected_levels; // Array of integers like [1, 2, 5]
        
        $user = SignUp::where('referCode', $referCode)->first();
        $referredBy = $user->referredBy;
        
        if (!$referredBy) {
            return back()->with('error', 'This user was not referred by anyone.');
        }

        $levels = [76, 35, 15, 10, 6, 5, 4, 3, 2, 2];
        $count = 0;

        DB::transaction(function () use ($levels, $referredBy, $referCode, $selectedLevels, &$count) {
            $uplinerRefer = $referredBy;
            $currentLevel = 1;

            while ($currentLevel <= count($levels) && $uplinerRefer) {
                $upliner = SignUp::where('referCode', $uplinerRefer)->first();
                
                if (!$upliner) break;

                // Check if this level is selected (if any selection was made)
                if (empty($selectedLevels) || in_array($currentLevel, $selectedLevels)) {
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
                        'created_at' => now()->format('d-m-Y h:i A'),
                        'date' => now()
                    ]);

                    // 3. Extra Perk for Level 1
                    if ($currentLevel === 1) {
                        $upliner->increment('math_game', 4);
                    }
                    
                    $count++;
                }

                $uplinerRefer = $upliner->referredBy;
                $currentLevel++;
            }
        });

        return back()->with('success', "Referral bonus successfully distributed through $count selected levels.");
    }

    public function referBonusHistory(Request $request)
    {
        $query = Transaction::with('user')
            ->where(function($q) {
                $q->where('payment_gateway', 'Referral Bonus')
                  ->orWhere('type', 'commission');
            });

        if ($request->filled('refer_code')) {
            $query->where('refer_id', 'like', '%' . $request->refer_code . '%');
        }

        $bonuses = $query->orderBy('id', 'desc')->paginate(50);

        return view('admin.rewards.refer_bonus_history', compact('bonuses'));
    }

    public function editReferBonus(Request $request, $id)
    {
        $request->validate(['amount' => 'required|numeric|min:0']);
        
        $transaction = Transaction::findOrFail($id);
        $user = SignUp::findOrFail($transaction->user_id);
        
        $oldAmount = $transaction->amount;
        $newAmount = $request->amount;
        $difference = $newAmount - $oldAmount;

        DB::transaction(function () use ($transaction, $user, $newAmount, $difference) {
            // Update Transaction
            $transaction->update(['amount' => $newAmount]);
            
            // Adjust User Balance
            $user->increment('wallet_balance', $difference);
        });

        return back()->with('success', "Bonus updated successfully. User balance adjusted by ৳$difference.");
    }

    public function deleteReferBonus($id)
    {
        $transaction = Transaction::findOrFail($id);
        $user = SignUp::findOrFail($transaction->user_id);
        $amount = $transaction->amount;

        DB::transaction(function () use ($transaction, $user, $amount) {
            // Subtract from balance
            $user->decrement('wallet_balance', $amount);
            
            // Delete transaction
            $transaction->delete();
        });

        return back()->with('success', "Bonus deleted successfully. ৳$amount subtracted from user's balance.");
    }
}
