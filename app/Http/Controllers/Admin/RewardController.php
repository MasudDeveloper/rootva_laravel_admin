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

        // Find all referrers with >= 2 approved verifications yesterday
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
            return back()->with('error', 'No user qualified for the daily bonus yesterday (minimum 2 verifications required).');
        }

        $count = 0;
        $totalAmount = 0;

        DB::transaction(function () use ($referrers, $yesterday, &$count, &$totalAmount) {
            foreach ($referrers as $referrer) {
                // Determine bonus amount based on the new rules
                $total = $referrer->total;
                $amount = 0;
                if ($total == 2) $amount = 20.00;
                elseif ($total == 3) $amount = 30.00;
                elseif ($total == 4) $amount = 40.00;
                elseif ($total >= 5) $amount = 60.00;

                if ($amount == 0) continue;

                // Check if already given for yesterday's work
                $exists = Transaction::where('user_id', $referrer->user_id)
                    ->where('payment_gateway', 'Daily Bonus')
                    ->whereDate('created_at', Carbon::today())
                    ->exists();

                if ($exists) {
                    continue;
                }

                $user = SignUp::find($referrer->user_id);
                if (!$user) continue;

                // 1. Update Balance
                $user->increment('wallet_balance', $amount);

                // 2. Log Transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => $user->referCode,
                    'amount' => $amount,
                    'type' => 'income',
                    'payment_gateway' => 'Daily Bonus',
                    'description' => "🎉 Daily Target Bonus for {$referrer->total} verifications on " . $yesterday->format('Y-m-d'),
                    'update_at' => now()->format('d-m-Y h:i A'),
                    'created_at' => now()->format('d-m-Y h:i A'),
                    'date' => now()
                ]);

                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'message' => "অভিনন্দন! গতকালের ডেইলি টার্গেট পূরণের জন্য আপনি ৳{$amount} বোনাস পেয়েছেন।",
                    'is_read' => 0,
                    'created_at' => now()->format('d-m-Y h:i A')
                ]);

                $count++;
                $totalAmount += $amount;
            }
        });

        if ($count == 0) {
            return back()->with('error', 'Qualified users have already received their daily bonus today.');
        }

        return back()->with('success', "Daily target bonus successfully awarded to {$count} users. Total amount: ৳{$totalAmount}!");
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

        // Check if weekly bonus already distributed recently
        $exists = Transaction::where('payment_gateway', 'Weekly Bonus')
            ->where('date', '>=', Carbon::now()->subDays(6))
            ->exists();

        if ($exists) {
            return back()->with('error', 'Weekly bonus has already been distributed for this week.');
        }

        // Logic: All referrers of the last 7 days with >= 10 verifications
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
            return back()->with('error', 'No user qualified for the weekly bonus (minimum 10 verifications required).');
        }

        $count = 0;
        $totalAmount = 0;

        DB::transaction(function () use ($referrers, &$count, &$totalAmount) {
            foreach ($referrers as $referrer) {
                $total = $referrer->total;
                $amount = 0;

                if ($total >= 30) {
                    $amount = 400;
                } elseif ($total >= 20) {
                    $amount = 200;
                } elseif ($total >= 15) {
                    $amount = 150;
                } elseif ($total >= 10) {
                    $amount = 100;
                }

                if ($amount > 0) {
                    $user = SignUp::find($referrer->user_id);
                    if ($user) {
                        $user->increment('wallet_balance', $amount);

                        Transaction::create([
                            'user_id' => $user->id,
                            'refer_id' => $user->referCode,
                            'amount' => $amount,
                            'type' => 'income',
                            'payment_gateway' => 'Weekly Bonus',
                            'description' => "🎉 Weekly Target Bonus for {$total} verifications in 7 days",
                            'update_at' => now()->format('d-m-Y h:i A'),
                            'created_at' => now()->format('d-m-Y h:i A'),
                            'date' => now()
                        ]);

                        \App\Models\Notification::create([
                            'user_id' => $user->id,
                            'message' => "অভিনন্দন! আপনি উইকলি টার্গেট বোনাস হিসেবে ৳{$amount} পেয়েছেন।",
                            'is_read' => 0,
                            'created_at' => now()->format('d-m-Y h:i A')
                        ]);

                        $count++;
                        $totalAmount += $amount;
                    }
                }
            }
        });

        return back()->with('success', "Weekly bonus of ৳{$totalAmount} successfully distributed to {$count} users!");
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

                    \App\Models\Notification::create([
                        'user_id' => $upliner->id,
                        'message' => "অভিনন্দন! লেভেল $currentLevel এফিলিয়েট হিসেবে আপনি ৳{$bonus} রেফার বোনাস পেয়েছেন।",
                        'is_read' => 0,
                        'created_at' => now()->format('d-m-Y h:i A')
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

    /**
     * Date-wise Bulk Bonus Distribution Section
     */
    public function dateBonusIndex()
    {
        $recentBonuses = Transaction::with('user')
            ->whereIn('payment_gateway', ['Registration Bonus', 'Special Bonus', 'Daily Bonus', 'Date Bonus', 'Verification Bonus'])
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.rewards.date_bonus', compact('recentBonuses'));
    }

    public function distributeDateBonus(Request $request)
    {
        $request->validate([
            'target_date' => 'required|date',
            'bonus_type' => 'required|in:referrer_bonus,verified_user_bonus,all_verified_bonus',
            'amount' => 'required|numeric|min:1',
            'payment_gateway' => 'required|string|max:100',
            'description' => 'required|string',
        ]);

        $targetDate = $request->target_date; // e.g. '2026-07-24'
        $amount = (float) $request->amount;
        $gateway = $request->payment_gateway;
        $desc = $request->description;

        $count = 0;
        $totalAmount = 0;

        if ($request->bonus_type === 'referrer_bonus') {
            // ১. নির্দিষ্ট তারিখে ভেরিফাই হওয়া ইউজারদের রেফারারদের বোনাস (যেমন রেজিস্ট্রেশন রেফার বোনাস)
            $approvedRequests = VerificationRequest::where('status', 'Approved')
                ->where(function($q) use ($targetDate) {
                    $q->whereDate('verified_raw_time', $targetDate)
                      ->orWhere('updated_at', 'like', $targetDate . '%');
                })
                ->whereNotNull('refer_id')
                ->where('refer_id', '!=', '')
                ->get();

            DB::transaction(function () use ($approvedRequests, $amount, $gateway, $desc, &$count, &$totalAmount) {
                foreach ($approvedRequests as $vr) {
                    $referrer = SignUp::where('referCode', $vr->refer_id)->first();
                    $verifiedUser = SignUp::find($vr->user_id);

                    if ($referrer && $verifiedUser) {
                        $exists = Transaction::where('user_id', $referrer->id)
                            ->where('refer_id', $verifiedUser->referCode)
                            ->where('payment_gateway', $gateway)
                            ->exists();

                        if (!$exists) {
                            $referrer->increment('wallet_balance', $amount);

                            Transaction::create([
                                'user_id' => $referrer->id,
                                'refer_id' => $verifiedUser->referCode,
                                'amount' => $amount,
                                'type' => 'income',
                                'payment_gateway' => $gateway,
                                'description' => $desc,
                                'update_at' => now()->format('d-m-Y h:i A'),
                                'created_at' => now()->format('d-m-Y h:i A'),
                                'date' => now()
                            ]);

                            \App\Models\Notification::create([
                                'user_id' => $referrer->id,
                                'message' => "অভিনন্দন! আপনি ৳{$amount} বোনাস পেয়েছেন।",
                                'is_read' => 0,
                                'created_at' => now()->format('d-m-Y h:i A')
                            ]);

                            $count++;
                            $totalAmount += $amount;
                        }
                    }
                }
            });

            return back()->with('success', "সফলভাবে $count টি রেফার বোনাস বিতরণ করা হয়েছে! মোট বিতরণ: ৳" . number_format($totalAmount, 2));
        } elseif ($request->bonus_type === 'verified_user_bonus') {
            // ২. নির্দিষ্ট তারিখে ভেরিফাই/রেজিস্ট্রেশনকৃত ইউজারদের সরাসরি বোনাস
            $users = SignUp::whereIn('is_verified', [1, 3])
                ->where(function($q) use ($targetDate) {
                    $q->whereDate('verified_raw_time', $targetDate)
                      ->orWhere('verified_at', 'like', date('d-m-Y', strtotime($targetDate)) . '%')
                      ->orWhereDate('created_at', $targetDate);
                })
                ->get();

            DB::transaction(function () use ($users, $amount, $gateway, $desc, &$count, &$totalAmount, $targetDate) {
                foreach ($users as $user) {
                    $exists = Transaction::where('user_id', $user->id)
                        ->where('payment_gateway', $gateway)
                        ->whereDate('date', $targetDate)
                        ->exists();

                    if (!$exists) {
                        $user->increment('wallet_balance', $amount);

                        Transaction::create([
                            'user_id' => $user->id,
                            'refer_id' => $user->referCode,
                            'amount' => $amount,
                            'type' => 'income',
                            'payment_gateway' => $gateway,
                            'description' => $desc,
                            'update_at' => now()->format('d-m-Y h:i A'),
                            'created_at' => now()->format('d-m-Y h:i A'),
                            'date' => now()
                        ]);

                        \App\Models\Notification::create([
                            'user_id' => $user->id,
                            'message' => "অভিনন্দন! আপনি ৳{$amount} বোনাস পেয়েছেন।",
                            'is_read' => 0,
                            'created_at' => now()->format('d-m-Y h:i A')
                        ]);

                        $count++;
                        $totalAmount += $amount;
                    }
                }
            });

            return back()->with('success', "সফলভাবে $count জন ভেরিফাইড ইউজারকে বোনাস বিতরণ করা হয়েছে! মোট বিতরণ: ৳" . number_format($totalAmount, 2));
        } elseif ($request->bonus_type === 'all_verified_bonus') {
            // ৩. সিস্টেমের সকল ভেরিফাইড ইউজারকে বোনাস
            $users = SignUp::whereIn('is_verified', [1, 3])->get();

            DB::transaction(function () use ($users, $amount, $gateway, $desc, &$count, &$totalAmount) {
                foreach ($users as $user) {
                    $exists = Transaction::where('user_id', $user->id)
                        ->where('payment_gateway', $gateway)
                        ->whereDate('date', now())
                        ->exists();

                    if (!$exists) {
                        $user->increment('wallet_balance', $amount);

                        Transaction::create([
                            'user_id' => $user->id,
                            'refer_id' => $user->referCode,
                            'amount' => $amount,
                            'type' => 'income',
                            'payment_gateway' => $gateway,
                            'description' => $desc,
                            'update_at' => now()->format('d-m-Y h:i A'),
                            'created_at' => now()->format('d-m-Y h:i A'),
                            'date' => now()
                        ]);

                        \App\Models\Notification::create([
                            'user_id' => $user->id,
                            'message' => "অভিনন্দন! আপনি ৳{$amount} বোনাস পেয়েছেন।",
                            'is_read' => 0,
                            'created_at' => now()->format('d-m-Y h:i A')
                        ]);

                        $count++;
                        $totalAmount += $amount;
                    }
                }
            });

            return back()->with('success', "সফলভাবে সকল ভেরিফাইড ($count জন) ইউজারকে বোনাস বিতরণ করা হয়েছে! মোট বিতরণ: ৳" . number_format($totalAmount, 2));
        }

        return back()->with('error', 'অকার্যকর বোনাস অপশন নির্বাচিত হয়েছে।');
    }
}
