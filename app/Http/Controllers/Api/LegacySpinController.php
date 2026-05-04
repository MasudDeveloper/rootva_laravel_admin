<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WheelSpinInfo;
use App\Models\SpinHistory;
use App\Models\SignUp;
use App\Models\BonusTracker;
use App\Models\Transaction;
use App\Models\VerificationRequest;
use Illuminate\Support\Facades\DB;

class LegacySpinController extends Controller
{
    /**
     * Legacy Spin Data (get_spin_data.php)
     */
    public function getSpinData(Request $request)
    {
        $user_id = intval($request->input('user_id', 0));

        if ($user_id <= 0) {
            return response()->json(['error' => true, 'message' => 'Invalid user ID']);
        }

        try {
            return DB::transaction(function () use ($user_id) {
                // Check if user exists in wheel_spin_info
                $info = WheelSpinInfo::where('user_id', $user_id)->first();

                if (!$info) {
                    // Create new record for new user
                    $current_time = now();
                    $info = WheelSpinInfo::create([
                        'user_id' => $user_id,
                        'total_spin' => 0,
                        'free_spin_used' => 0,
                        'spin_balance' => 0.00,
                        'last_spin_at' => $current_time,
                        'claimed' => 0
                    ]);

                    return response()->json([
                        'error' => false,
                        'message' => 'New user spin info created',
                        'spin_balance' => 0,
                        'total_spin' => 0,
                        'free_spin_used' => 0,
                        'last_spin_at' => $current_time->toDateTimeString(),
                        'claimed' => false
                    ]);
                } else {
                    // Return existing user data
                    return response()->json([
                        'error' => false,
                        'message' => 'Spin info loaded',
                        'spin_balance' => (float)$info->spin_balance,
                        'total_spin' => (int)$info->total_spin,
                        'free_spin_used' => (int)$info->free_spin_used,
                        'last_spin_at' => $info->last_spin_at,
                        'claimed' => (bool)$info->claimed
                    ]);
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Legacy Spin Wheel (spin_wheel.php)
     */
    public function submitSpinResult(Request $request)
    {
        $user_id = intval($request->input('user_id', 0));
        $amount = intval($request->input('amount', 0));

        if ($user_id <= 0 || $amount <= 0) {
            return response()->json(['error' => true, 'message' => 'Invalid input']);
        }

        try {
            return DB::transaction(function () use ($user_id, $amount) {
                // Check user exists
                $user = SignUp::find($user_id);
                if (!$user) {
                    return response()->json(['error' => true, 'message' => 'User not found']);
                }

                // Get or insert wheel_spin_info
                $info = WheelSpinInfo::where('user_id', $user_id)->lockForUpdate()->first();
                
                if (!$info) {
                    $info = WheelSpinInfo::create([
                        'user_id' => $user_id,
                        'total_spin' => 0,
                        'free_spin_used' => 0,
                        'spin_balance' => 0.00,
                        'last_spin_at' => null,
                        'claimed' => 0
                    ]);
                }

                // 🛑 Check if spin bonus already claimed
                if ($info->claimed == 1) {
                    return response()->json(['error' => true, 'message' => 'আপনি ইতিমধ্যেই স্পিন বোনাস ক্লেইম করেছেন']);
                }

                $canSpin = false;
                $free_spin = false;

                // Free spin check
                if ($info->free_spin_used < 5) {
                    $canSpin = true;
                    $free_spin = true;
                } else {
                    // Check verification
                    $isVerified = VerificationRequest::where('user_id', $user_id)
                        ->where('status', 'Approved')
                        ->exists();

                    if (!$isVerified) {
                        return response()->json(['error' => true, 'message' => 'Please verify your account to continue spinning']);
                    }

                    // Cooldown check
                    if (!empty($info->last_spin_at)) {
                        $lastSpin = strtotime($info->last_spin_at);
                        $remaining = (6 * 3600) - (time() - $lastSpin);
                        if ($remaining > 0) {
                            $minutes = ceil($remaining / 60);
                            return response()->json(['error' => true, 'message' => "Please wait $minutes minutes before spinning again"]);
                        }
                    }

                    $canSpin = true;
                }

                if ($canSpin) {
                    $new_balance = $info->spin_balance + $amount;
                    $total_spins = $info->total_spin + 1;
                    $free_spins_used = $free_spin ? ($info->free_spin_used + 1) : $info->free_spin_used;
                    $current_time = now();

                    // Update wheel_spin_info
                    $info->update([
                        'total_spin' => $total_spins,
                        'free_spin_used' => $free_spins_used,
                        'spin_balance' => $new_balance,
                        'last_spin_at' => $current_time
                    ]);

                    // Insert into spin_history
                    SpinHistory::create([
                        'user_id' => $user_id,
                        'amount' => $amount,
                        'is_free_spin' => $free_spin ? 1 : 0,
                        'created_at' => $current_time
                    ]);

                    return response()->json([
                        'error' => false,
                        'message' => 'Spin Bonus Added',
                        'amount' => $amount,
                        'total_balance' => $new_balance,
                        'free_spins_used' => $free_spins_used,
                        'is_free_spin' => $free_spin,
                        'last_spin_at' => $current_time->toDateTimeString()
                    ]);
                }
                
                return response()->json(['error' => true, 'message' => 'Unknown error']);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Legacy Claim Spin Bonus (claim_spin_bonus.php)
     */
    public function claimSpinBonus(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId) return response()->json(['error' => true, 'message' => 'Invalid user ID']);

        try {
            return DB::transaction(function () use ($userId) {
                $spinInfo = WheelSpinInfo::where('user_id', $userId)->lockForUpdate()->first();

                if (!$spinInfo) {
                    throw new \Exception("User spin info not found");
                }

                if ($spinInfo->spin_balance < 200) {
                    throw new \Exception("Minimum ৳200 required to claim");
                }

                if ($spinInfo->claimed == 1) {
                    throw new \Exception("Already claimed");
                }

                $amount = 200;
                $currentTime = date("d-m-Y h:i A");

                Transaction::create([
                    'user_id' => $userId,
                    'amount' => $amount,
                    'type' => 'income',
                    'payment_gateway' => 'Spin Bonus',
                    'description' => 'Spin bonus claim',
                    'created_at' => $currentTime,
                    'update_at' => $currentTime,
                    'date' => now()
                ]);

                $spinInfo->update([
                    'spin_balance' => 0,
                    'claimed' => 1
                ]);

                return response()->json([
                    'error' => false,
                    'message' => 'Successfully claimed ৳200',
                    'transaction_id' => "SPIN-" . time() . "-" . $userId,
                    'new_balance' => 0,
                    'spin_balance' => 0,
                    'claimed' => true
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get Spin Progress (get_spin_progress.php)
     */
    public function getSpinProgress(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['error' => true, 'message' => 'User ID missing']);
        }

        $user = SignUp::where('id', $userId)->first(['referCode']);
        if (!$user) {
            return response()->json(['error' => true, 'message' => 'User not found']);
        }

        $referCode = $user->referCode;

        // Step 1: Get Level 1 referrals
        $level1 = SignUp::where('referredBy', $referCode)->get(['id', 'referCode']);
        $level1Ids = $level1->pluck('id')->toArray();
        $level1ReferCodes = $level1->pluck('referCode')->filter()->toArray();

        // Step 2: Count verified Level 1
        $level1_verified = 0;
        if (!empty($level1Ids)) {
            $level1_verified = VerificationRequest::where('status', 'Approved')
                ->whereIn('user_id', $level1Ids)
                ->count();
        }

        // Step 3: Get Level 2 referrals
        $level2Ids = [];
        if (!empty($level1ReferCodes)) {
            $level2Ids = SignUp::whereIn('referredBy', $level1ReferCodes)
                ->pluck('id')
                ->toArray();
        }

        // Step 4: Count verified Level 2
        $level2_verified = 0;
        if (!empty($level2Ids)) {
            $level2_verified = VerificationRequest::where('status', 'Approved')
                ->whereIn('user_id', $level2Ids)
                ->count();
        }

        // Step 5: Check if bonus already given
        $already_given = BonusTracker::where('user_id', $userId)
            ->where('bonus_type', 'spin_target')
            ->exists();

        $eligible = ($level1_verified >= 10 && $level2_verified >= 10 && !$already_given);

        return response()->json([
            'error' => false,
            'level1_verified' => (int)$level1_verified,
            'level2_verified' => (int)$level2_verified,
            'eligible' => (bool)$eligible
        ]);
    }

    /**
     * Legacy Spin Target Bonus (check_spin_target_bonus.php)
     */
    public function checkSpinTargetBonus(Request $request)
    {
        $user_id = $request->query('user_id');
        if (!$user_id) return response()->json(["status" => "error", "message" => "User ID missing."]);

        $bonus_type = 'spin_target';
        $bonus_amount = 500.00;

        if (BonusTracker::where('user_id', $user_id)->where('bonus_type', $bonus_type)->exists()) {
            return response()->json(["status" => "already_given", "message" => "Bonus already given to this user."]);
        }

        $user = SignUp::find($user_id);
        $level1 = SignUp::where('referredBy', $user->referCode)->get();
        
        if ($level1->count() < 10) {
            return response()->json(["status" => "incomplete", "message" => "Not enough Level 1 referrals.", "level1" => $level1->count()]);
        }

        $level1_verified = VerificationRequest::whereIn('user_id', $level1->pluck('id'))->where('status', 'Approved')->count();
        if ($level1_verified < 10) {
            return response()->json(["status" => "incomplete", "message" => "Not enough verified users in Level 1.", "level1_verified" => $level1_verified]);
        }

        $level2 = SignUp::whereIn('referredBy', $level1->pluck('referCode'))->get();
        if ($level2->count() < 10) {
            return response()->json(["status" => "incomplete", "message" => "Not enough Level 2 referrals.", "level2" => $level2->count()]);
        }

        $level2_verified = VerificationRequest::whereIn('user_id', $level2->pluck('id'))->where('status', 'Approved')->count();
        if ($level2_verified < 10) {
            return response()->json(["status" => "incomplete", "message" => "Not enough verified users in Level 2.", "level2_verified" => $level2_verified]);
        }

        // Target Met
        BonusTracker::create(['user_id' => $user_id, 'bonus_type' => $bonus_type, 'amount' => $bonus_amount]);
        Transaction::create([
            'user_id' => $user_id,
            'amount' => $bonus_amount,
            'type' => 'income',
            'payment_gateway' => 'bonus',
            'description' => "🎁 Spin Target Bonus (Level1: $level1_verified, Level2: $level2_verified)",
            'created_at' => now()->format('Y-m-d H:i:s'),
            'update_at' => now()->format('Y-m-d H:i:s'),
            'date' => now()
        ]);

        return response()->json([
            "error" => false,
            "status" => "success",
            "message" => "✅ Spin Target Bonus added!",
            "amount" => (int)$bonus_amount,
            "level1_verified" => (int)$level1_verified,
            "level2_verified" => (int)$level2_verified
        ]);
    }
}
