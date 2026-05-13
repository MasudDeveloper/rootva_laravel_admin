<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalaryRequest;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacySalaryController extends Controller
{
    /**
     * Get Salary Progress (get_salary_progress.php)
     */
    public function getSalaryProgress(Request $request)
    {
        $userId = $request->query('user_id');
        
        if (!$userId) {
            return response()->json(['error' => true, 'message' => 'User ID missing']);
        }

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json(['error' => true, 'message' => 'User not found']);
        }

        $referCode = $user->referCode;

        // 🟢 Step 1: Last monthly salary bonus date
        // 🟢 Step 1: Get last bonus claim time for this user
        $lastBonus = DB::table('bonus_tracker')
            ->where('user_id', $userId)
            ->where('bonus_type', 'monthly_salary')
            ->orderBy('created_at', 'desc')
            ->first();
        
        $startDate = $lastBonus ? $lastBonus->created_at : '2000-01-01 00:00:00';
        if (is_object($startDate)) {
            $startDate = $startDate->toDateTimeString();
        }

        // 🟢 Step 2: Fetch all Level 1 and Level 2 referrals in batches
        $level1 = DB::table('sign_up')
            ->where('referredBy', $referCode)
            ->get(['id', 'referCode', 'upline_changed_at']);

        if ($level1->isEmpty()) {
            return $this->formatSalaryResponse($referCode, 0, 0, 0, 0, false, [
                'debug_start_date' => $startDate,
                'debug_current_time' => now('Asia/Dhaka')->toDateTimeString()
            ]);
        }

        $level1Codes = $level1->pluck('referCode')->filter()->toArray();
        $level2 = DB::table('sign_up')
            ->whereIn('referredBy', $level1Codes)
            ->get(['id', 'referredBy', 'verified_raw_time']);

        // Group Level 2 by their referrer (Level 1 referCode) for easy access
        $level2ByParent = $level2->groupBy('referredBy');

        // 🟢 Step 3: Pre-fetch ALL relevant Approved verifications
        $allReferralIds = $level1->pluck('id')->merge($level2->pluck('id'))->unique()->toArray();
        $allVerifications = DB::table('verification_requests')
            ->whereIn('user_id', $allReferralIds)
            ->where('status', 'Approved')
            ->get(['user_id', 'verified_raw_time', 'updated_at'])
            ->groupBy('user_id');

        // 🟢 Step 4: Process statistics in memory
        $level1VerifiedCount = 0;
        $level1Active = 0;
        $level2VerifiedTotal = 0;

        $tz = 'Asia/Dhaka';
        $startTs = \Carbon\Carbon::parse($startDate, $tz)->getTimestamp();

        foreach ($level1 as $l1) {
            // Validate upline_changed_at to avoid zero-date issues
            $l1TransferDate = $l1->upline_changed_at;
            if ($l1TransferDate === '0000-00-00 00:00:00' || !$l1TransferDate) {
                $l1TransferDate = null;
            }

            $l1TransferTs = $l1TransferDate ? \Carbon\Carbon::parse($l1TransferDate, $tz)->getTimestamp() : 0;
            
            // 🔸 RULE: Verified must be AFTER (Latest of Last Salary OR Transfer Date)
            $l1FilterTs = max($startTs, $l1TransferTs);
            
            // Check L1 verified
            $l1Vers = $allVerifications->get($l1->id);
            $isL1Verified = false;
            if ($l1Vers) {
                foreach ($l1Vers as $v) {
                    $vTimeStr = $v->verified_raw_time;
                    if (($vTimeStr === '0000-00-00 00:00:00' || !$vTimeStr) && isset($v->updated_at)) {
                        $vTimeStr = $v->updated_at;
                    }

                    if ($vTimeStr && $vTimeStr !== '0000-00-00 00:00:00') {
                        try {
                            $vTs = null;
                            if (preg_match('/^\d{2}-\d{2}-\d{4}/', $vTimeStr)) {
                                $vTs = \Carbon\Carbon::createFromFormat('d-m-Y h:i A', $vTimeStr, $tz)->getTimestamp();
                            } else {
                                $vTs = \Carbon\Carbon::parse($vTimeStr, $tz)->getTimestamp();
                            }

                            if ($vTs > $l1FilterTs) {
                                $isL1Verified = true;
                            }
                        } catch (\Exception $e) {}
                    }
                }
            }
            
            if ($isL1Verified) {
                $level1VerifiedCount++;
            }

            // Check L2 verifications
            $l2OfThisL1 = $level2ByParent->get($l1->referCode, collect());
            $l2VerifiedOfThisL1ForActive = 0;

            foreach ($l2OfThisL1 as $l2Member) {
                $l2Vers = $allVerifications->get($l2Member->id);
                if ($l2Vers) {
                    foreach ($l2Vers as $v) {
                        $vTimeStr = $v->verified_raw_time;
                        if (($vTimeStr === '0000-00-00 00:00:00' || !$vTimeStr) && isset($v->updated_at)) {
                            $vTimeStr = $v->updated_at;
                        }

                        if ($vTimeStr && $vTimeStr !== '0000-00-00 00:00:00') {
                            try {
                                $vTs = null;
                                if (preg_match('/^\d{2}-\d{2}-\d{4}/', $vTimeStr)) {
                                    $vTs = \Carbon\Carbon::createFromFormat('d-m-Y h:i A', $vTimeStr, $tz)->getTimestamp();
                                } else {
                                    $vTs = \Carbon\Carbon::parse($vTimeStr, $tz)->getTimestamp();
                                }

                                // 1. Total Level 2 Verified
                                $l2TotalFilterTs = max($startTs, $l1TransferTs);
                                if ($vTs > $l2TotalFilterTs) {
                                    $level2VerifiedTotal++;
                                }

                                // 2. Count for Level 1 Active Check
                                $l2ActiveFilterTs = max($startTs, $l1TransferTs);
                                
                                if ($vTs > $l2ActiveFilterTs) {
                                    $l2VerifiedOfThisL1ForActive++;
                                }
                            } catch (\Exception $e) {}
                        }
                    }
                }
            }

            // Level 1 Active Condition: Verified + >= 2 verified Level 2
            if ($isL1Verified && $l2VerifiedOfThisL1ForActive >= 2) {
                $level1Active++;
            }
        }

        // 🟢 Step 5: Total Orders
        $totalOrders = DB::table('orders')
            ->where('user_id', $userId)
            ->where('order_status', 'Delivered')
            ->where('created_at', '>', $startDate)
            ->count();

        $isEligible = (
            $level1VerifiedCount >= 30 &&
            $level2VerifiedTotal >= 60 &&
            $level1Active >= 10 &&
            $totalOrders >= 1
        );

        return $this->formatSalaryResponse($referCode, $level1VerifiedCount, $level1Active, $level2VerifiedTotal, $totalOrders, $isEligible);
    }

    private function formatSalaryResponse($referCode, $l1v, $l1a, $l2v, $orders, $eligible)
    {
        return response()->json([
            'referCode' => (string)$referCode,
            'level1_verified' => (int)$l1v,
            'level1_active' => (int)$l1a,
            'level2_verified' => (int)$l2v,
            'eligible' => (bool)$eligible,
            'bonus_claimed' => false,
            'total_orders' => (int)$orders
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Apply Salary Request
     */
    public function applySalaryRequest(Request $request)
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID missing']);
        }

        // Check if already applied and pending
        $existing = SalaryRequest::where('user_id', $userId)
            ->where('request_type', 'monthly_salary')
            ->where('status', 'Pending')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false, 
                'message' => 'You have already applied. Please wait for admin approval.'
            ]);
        }

        // Insert new request
        $salaryRequest = SalaryRequest::create([
            'user_id'      => $userId,
            'request_type' => 'monthly_salary',
            'status'       => 'Pending',
            'requested_at' => now()
        ]);

        return response()->json([
            'success' => (bool)$salaryRequest,
            'message' => $salaryRequest ? 'Your application has been submitted!' : 'Failed to submit application.'
        ]);
    }

    /**
     * Get Salary Request Status
     */
    public function getSalaryRequestStatus(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['error' => true, 'message' => 'User ID missing']);
        }

        $latestRequest = SalaryRequest::where('user_id', $userId)
            ->where('request_type', 'monthly_salary')
            ->latest('requested_at')
            ->first();

        return response()->json([
            'status' => $latestRequest ? $latestRequest->status : 'None'
        ]);
    }
}
