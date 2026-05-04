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
        Log::info("Salary progress request for user: " . $userId);

        $user = SignUp::find($userId);

        if (!$user) {
            Log::warning("User not found for salary progress: " . $userId);
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        $referCode = $user->referCode;

        // 🟢 Step 1: Last monthly salary bonus date from bonus_tracker
        $lastBonus = DB::table('bonus_tracker')
            ->where('user_id', $userId)
            ->where('bonus_type', 'monthly_salary')
            ->latest('created_at')
            ->first();
        
        $startDate = $lastBonus ? $lastBonus->created_at : '2000-01-01 00:00:00';

        // 🟢 Step 2: Get Level 1 referrals
        $level1 = DB::table('sign_up')
            ->where('referredBy', $referCode)
            ->get(['id', 'referCode', 'upline_changed_at']);

        $level1VerifiedIds = [];
        $level1Active = 0;
        $level2VerifiedTotal = 0;

        foreach ($level1 as $l1) {
            $l1Id = $l1->id;
            $l1ReferCode = $l1->referCode;
            $transferDate = $l1->upline_changed_at;
            $filterDate = $transferDate ? $transferDate : $startDate;

            // 🔸 Check if Level 1 is verified after filterDate
            $isL1Verified = DB::table('verification_requests')
                ->where('user_id', $l1Id)
                ->where('status', 'Approved')
                ->where('verified_raw_time', '>', $filterDate)
                ->exists();

            if ($isL1Verified) {
                $level1VerifiedIds[] = $l1Id;
            }

            // 🔸 Get Level 2 (referrals of Level 1)
            if ($l1ReferCode) {
                $level2Ids = DB::table('sign_up')
                    ->where('referredBy', $l1ReferCode)
                    ->pluck('id')
                    ->toArray();

                if (!empty($level2Ids)) {
                    // 🔸 Count Level 2 verified after filterDate
                    $l2VerifiedCount = DB::table('verification_requests')
                        ->whereIn('user_id', $level2Ids)
                        ->where('status', 'Approved')
                        ->where('verified_raw_time', '>', $filterDate)
                        ->count();

                    $level2VerifiedTotal += $l2VerifiedCount;

                    // 🔹 Level-1 Active Condition: Verified + at least 2 verified Level 2
                    if ($isL1Verified && $l2VerifiedCount >= 2) {
                        $level1Active++;
                    }
                }
            }
        }

        // 🟢 Step 3: Total Orders (only 'Delivered')
        $totalOrders = DB::table('orders')
            ->where('user_id', $userId)
            ->where('order_status', 'Delivered')
            ->where('created_at', '>', $startDate)
            ->count();

        // 🟢 Step 4: Eligibility Check
        $level1VerifiedCount = count($level1VerifiedIds);
        $isEligible = ($level1VerifiedCount >= 30 && $level1Active >= 10 && $level2VerifiedTotal >= 60 && $totalOrders >= 1);

        // 🟢 Step 5: Status
        $lastRequest = SalaryRequest::where('user_id', $userId)
            ->where('request_type', 'monthly_salary')
            ->latest('requested_at')
            ->first();
        
        $status = $lastRequest ? $lastRequest->status : 'None';

        $responseData = [
            'success' => true,
            'referCode' => (string)$referCode,
            'level1_verified' => (int)$level1VerifiedCount,
            'level1_active' => (int)$level1Active,
            'level2_verified' => (int)$level2VerifiedTotal,
            'total_orders' => (int)$totalOrders,
            'eligible' => (bool)$isEligible,
            'status' => (string)$status,
            'admin_note' => (string)($lastRequest ? ($lastRequest->admin_note ?? '') : ''),
            'bonus_claimed' => false,
            'last_bonus_date' => (string)$startDate
        ];

        Log::info("Response for user " . $userId . ": " . json_encode($responseData));

        return response()->json($responseData);
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
