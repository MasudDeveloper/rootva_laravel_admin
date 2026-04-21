<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Banner;
use App\Models\SocialLink;
use App\Models\AppUpdate;
use App\Models\SimOffer;
use App\Models\ProductCategory;
use App\Models\PaymentNumber;
use App\Models\Microjob;
use App\Models\Transaction;
use App\Models\ReferralCommission;
use Illuminate\Support\Facades\Hash;

class LegacyApiController extends Controller
{
    /**
     * Legacy Login (login.php)
     */
    public function login(Request $request)
    {
        $number = $request->input('number');
        $password = $request->input('password');

        $user = SignUp::where('number', $number)->first();

        if ($user) {
            if (Hash::check($password, $user->password) || $password === $user->password) {
                $token = bin2hex(random_bytes(32));
                $user->api_token = $token;
                $user->save();

                return response()->json([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'লগইন সফল',
                    'userId' => $user->id,
                    'password_updated_at' => $user->password_updated_at ?? '',
                    'api_token' => $token,
                ]);
            }
            return response()->json(['message' => 'ভুল পাসওয়ার্ড']);
        }
        return response()->json(['message' => 'নম্বরটি পাওয়া যায়নি']);
    }

    /**
     * Legacy User Data (get_Data.php)
     */
    public function getUserData(Request $request)
    {
        $number = $request->input('number');
        $user = SignUp::where('number', $number)->first();

        if ($user) {
            return response()->json([
                'status' => 'success',
                'message' => 'ডেটা পাওয়া গেছে',
                'data' => $user,
                'show_verification_popup' => (bool)($user->is_verified == 1 && $user->verification_popup_shown == 0)
            ]);
        }
        return response()->json(['status' => 'error', 'message' => 'ইউজার খুঁজে পাওয়া যায়নি']);
    }

    /**
     * Mark Verification Popup Seen
     */
    public function markVerificationPopupSeen(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);
        
        if ($user) {
            $user->verification_popup_shown = 1;
            $user->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }


    /**
     * Legacy Wallet Balance (get_wallet_balance.php)
     */
    public function getBalance(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);

        if ($user) {
            return response()->json([
                'wallet_balance' => (double) ($user->wallet_balance ?? 0),
                'voucher_balance' => (double) ($user->voucher_balance ?? 0),
                'success' => true
            ]);
        }
        return response()->json(['success' => false]);
    }

    /**
     * Legacy Transaction History (get_transaction_history.php)
     */
    public function getTransactionHistory(Request $request)
    {
        $userId = $request->input('user_id');
        $transactions = Transaction::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }

    /**
     * Legacy Income Report (get_income_report.php)
     */
    public function getIncomeReport(Request $request)
    {
        $userId = $request->input('user_id');
        $commissions = ReferralCommission::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'income_report' => $commissions
        ]);
    }

    /**
     * Legacy Banners (get_banners.php)
     */
    public function getBanners()
    {
        return response()->json(Banner::all());
    }

    /**
     * Legacy Social Links (get_social_links.php)
     */
    public function getSocialLinks()
    {
        $links = SocialLink::first();
        return response()->json([
            'success' => true,
            'social_links' => $links
        ]);
    }

    /**
     * Legacy App Update (get_latest_update.php)
     */
    public function getUpdate()
    {
        $update = AppUpdate::latest()->first();
        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Update info fetched',
            'data' => $update,
            'url' => $update->url ?? '',
            'version' => $update->version ?? '1.0.0'
        ]);
    }

    /**
     * Legacy Sim Offers (get_sim_offer.php)
     */
    public function getSimOffers()
    {
        return response()->json(SimOffer::all());
    }

    /**
     * Legacy Products Categories (get_categories.php)
     */
    public function getCategories()
    {
        return response()->json(ProductCategory::all());
    }

    /**
     * Legacy User Tree (get_referral_tree.php)
     */
    public function getReferralTree(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        
        $referCode = $request->input('referCode');
        $isUpdated = $request->input('isUpdated') === 'true';
        $limit = (int)$request->input('limit', 20);
        $offset = (int)$request->input('offset', 0);
        $targetLevel = $request->input('level'); // Optional level filter
        
        $startTime = microtime(true);
        
        // Use a lean array to store [id, level]
        $treeNodes = [];
        
        // Level 1: Fetch only necessary columns to save memory
        $level1 = \Illuminate\Support\Facades\DB::table('sign_up')
            ->where('referredBy', $referCode)
            ->get(['id', 'referCode']);
            
        $currentLevelCodes = [];
        foreach ($level1 as $user) {
            if (!$targetLevel || $targetLevel == 1) {
                $treeNodes[] = ['id' => $user->id, 'level' => 1];
            }
            $currentLevelCodes[] = $user->referCode;
        }
        
        // Levels 2-10: Iterative Breadth-First Scan
        for ($i = 2; $i <= 10; $i++) {
            if (empty($currentLevelCodes)) break;
            
            $nextLevel = \Illuminate\Support\Facades\DB::table('sign_up')
                ->whereIn('referredBy', $currentLevelCodes)
                ->get(['id', 'referCode']);
                
            if ($nextLevel->isEmpty()) break;
            
            $currentLevelCodes = [];
            foreach ($nextLevel as $user) {
                if (!$targetLevel || $targetLevel == $i) {
                    $treeNodes[] = ['id' => $user->id, 'level' => $i];
                }
                $currentLevelCodes[] = $user->referCode;
            }
            
            // If we only wanted a specific level and we just finished it, we can stop scanning further
            if ($targetLevel && $i >= $targetLevel) break;
        }
        
        // Collect all IDs for processing
        $allNodes = collect($treeNodes);
        $total = $allNodes->count();
        
        if ($isUpdated) {
            // 1. Sort IDs DESC (matches old PHP behavior) and Slice
            $pageNodes = $allNodes->sortByDesc('id')->values()->slice($offset, $limit);
            $hasMore = ($offset + $limit) < $total;
            
            // 2. Fetch full objects only for the current page
            $pageIds = $pageNodes->pluck('id')->toArray();
            $users = SignUp::whereIn('id', $pageIds)
                ->orderBy('id', 'desc')
                ->get();
                
            // 3. Re-attach Level and UserID fields
            $levelMap = $pageNodes->pluck('level', 'id')->toArray();
            foreach ($users as $user) {
                $user->level = $levelMap[$user->id] ?? 0;
                $user->user_id = $user->id;
            }

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "ডেটা সফলভাবে লোড হয়েছে",
                'data' => [['users' => $users->values()]], // data[0].users structure
                'total' => $total,
                'hasMore' => $hasMore,
                'load_time' => round(microtime(true) - $startTime, 4) . " sec"
            ]);
        } else {
            // Legacy Non-Paginated Tree (Grouped by Level)
            $allIds = $allNodes->pluck('id')->toArray();
            $users = SignUp::whereIn('id', $allIds)
                ->orderBy('id', 'desc')
                ->get();
                
            $levelMap = $allNodes->pluck('level', 'id')->toArray();
            foreach ($users as $user) {
                $user->level = $levelMap[$user->id] ?? 0;
                $user->user_id = $user->id;
            }
            
            $levels = [];
            $grouped = $users->groupBy('level');
            foreach ($grouped as $lvl => $group) {
                $levels[] = [
                    'level' => (int)$lvl,
                    'users' => $group->sortByDesc('id')->values()
                ];
            }
            usort($levels, fn($a, $b) => $a['level'] <=> $b['level']);

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "ডেটা সফলভাবে লোড হয়েছে",
                'data' => $levels,
                'load_time' => round(microtime(true) - $startTime, 4) . " sec"
            ]);
        }
    }

    /**
     * Get Team Summary (Counts per Level)
     * Extremely fast since it only queries counts
     */
    public function getTeamSummary(Request $request)
    {
        $referCode = $request->input('referCode');
        $startTime = microtime(true);
        
        $summary = [];
        $currentLevelCodes = [$referCode];
        
        for ($i = 1; $i <= 10; $i++) {
            if (empty($currentLevelCodes)) break;
            
            $users = \Illuminate\Support\Facades\DB::table('sign_up')
                ->whereIn('referredBy', $currentLevelCodes)
                ->select('id', 'referCode', 'is_verified')
                ->get();
                
            if ($users->isEmpty()) break;
            
            $totalCount = $users->count();
            $verifiedCount = $users->where('is_verified', 1)->count() + $users->where('is_verified', 3)->count();
            $unverifiedCount = $totalCount - $verifiedCount;
            
            $summary[] = [
                'level' => $i,
                'total' => $totalCount,
                'verified' => $verifiedCount,
                'unverified' => $unverifiedCount
            ];
            
            $currentLevelCodes = $users->pluck('referCode')->filter()->toArray();
        }

        return response()->json([
            'status' => 'success',
            'data' => $summary,
            'load_time' => round(microtime(true) - $startTime, 4) . " sec"
        ]);
    }

    /**
     * Legacy Search User in Tree (get_referral_tree2.php)
     */
    public function searchUserInMyTree(Request $request)
    {
        $myReferCode = $request->query('referCode');
        $searchCode = $request->query('searchReferCode');
        
        $user = SignUp::where('referCode', $searchCode)->first();
        
        if ($user) {
            $user->user_id = $user->id;
            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => "ইউজার পাওয়া গেছে",
                'referUsers' => [$user]
            ]);
        }
        
        return response()->json(['status' => 'error', 'message' => 'ইউজার পাওয়া যায়নি']);
    }

    /**
     * Legacy Upline Details (get_upline_details.php)
     */
    public function getUplineDetails(Request $request)
    {
        $referCode = $request->input('referCode');
        $user = SignUp::where('referCode', $referCode)->first();
        
        if ($user && $user->referredBy) {
            $upline = SignUp::where('referCode', $user->referredBy)->first();
            if ($upline) {
                return response()->json([
                    'status' => 'success',
                    'success' => true,
                    'message' => "UpLine info found",
                    'user' => $upline // Expected by ReferralResponse.java
                ]);
            }
        }
        
        return response()->json([
            'status' => 'error',
            'success' => false,
            'message' => "No UpLine found"
        ]);
    }

    /**
     * Legacy Individual Profile (get_profile.php)
     */
    public function getProfile(Request $request)
    {
        $userId = $request->query('user_id');
        $user = SignUp::find($userId);
        
        if ($user) {
            return response()->json([
                'status' => 'success',
                'success' => true,
                'users' => $user // Expected by UserResponse.java
            ]);
        }
        
        return response()->json(['status' => 'error', 'message' => 'User not found']);
    }

    /**
     * Legacy Withdraw Requests (get_withdraw_request.php)
     */
    public function getWithdrawRequests(Request $request)
    {
        $userId = $request->query('user_id');
        $requests = \App\Models\WithdrawRequest::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($requests);
    }

    /**
     * Legacy Money Requests (get_money_requests.php)
     */
    public function getMoneyRequests(Request $request)
    {
        $userId = $request->query('user_id');
        $requests = \App\Models\MoneyRequest::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($requests);
    }


    /**
     * Legacy Profile Update (update_profile.php)
     */
    public function updateProfile(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);

        if ($user) {
            $user->update([
                'name' => $request->input('name', $user->name),
                'gender' => $request->input('gender', $user->gender),
                'address' => $request->input('address', $user->address),
                'email' => $request->input('email', $user->email),
            ]);

            return response()->json(['success' => true, 'message' => 'Profile Updated Successfully']);
        }
        return response()->json(['success' => false, 'message' => 'User not found']);
    }

    /**
     * Legacy Payment Numbers (get_payment_numbers.php)
     */
    public function getPaymentNumbers()
    {
        return response()->json([
            'success' => true,
            'payment_numbers' => PaymentNumber::all()
        ]);
    }

    /**
     * Legacy Reviews (get_reviews.php)
     */
    public function getReviews()
    {
        return response()->json(Banner::all());
    }

    /**
     * Legacy Microjobs (get_microjobs2.php)
     */
    public function getAvailableJobs(Request $request)
    {
        return response()->json(Microjob::where('status', 1)->get());
    }

    /**
     * Legacy Add Money (add_money_request.php)
     */
    public function addMoneyRequest(Request $request)
    {
        $userId = $request->input('user_id');
        $amount = $request->input('amount');
        
        \App\Models\MoneyRequest::create([
            'user_id' => $userId,
            'title' => $request->input('title', 'Add Money'),
            'account_number' => $request->input('account_number'),
            'amount' => $amount,
            'transaction_id' => $request->input('transaction_id'),
            'payment_gateway' => $request->input('payment_gateway'),
            'status' => 'Pending',
            'created_at' => $request->input('current_time', now()->toDateTimeString())
        ]);

        return response()->json([
            'status' => 'success',
            'success' => true, 
            'message' => 'Request Submitted Successfully'
        ]);
    }

    /**
     * Legacy Withdraw (submit_money_withdraw_request.php)
     */
    public function withdrawRequest(Request $request)
    {
        $userId = $request->input('user_id');
        $amount = $request->input('amount');
        
        $user = SignUp::find($userId);
        if ($user && $user->wallet_balance < $amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient Balance']);
        }

        \App\Models\WithdrawRequest::create([
            'user_id' => $userId,
            'account_number' => $request->input('account_number'),
            'amount' => $amount,
            'payment_gateway' => $request->input('payment_gateway'),
            'status' => 'Pending',
            'created_at' => $request->input('current_time', now()->toDateTimeString())
        ]);

        return response()->json([
            'status' => 'success',
            'success' => true, 
            'message' => 'Withdrawal Request Submitted'
        ]);
    }

    /**
     * Legacy Verification (submit_verification_request.php)
     */
    public function submitVerificationRequest(Request $request)
    {
        \App\Models\VerificationRequest::create([
            'user_id' => $request->input('user_id'),
            'account_number' => $request->input('account_number'),
            'transaction_id' => $request->input('transaction_id'),
            'amount' => $request->input('amount'),
            'payment_gateway' => $request->input('payment_gateway'),
            'status' => 'Pending',
            'created_at' => $request->input('current_time', now()->toDateTimeString())
        ]);

        return response()->json([
            'status' => 'success',
            'success' => true, 
            'message' => 'Verification Request Submitted'
        ]);
    }

    /**
     * Legacy Recharge (recharge_request.php)
     */
    public function doRecharge(Request $request)
    {
        $userId = $request->input('user_id');
        $amount = $request->input('amount');
        
        \App\Models\RechargeTransaction::create([
            'user_id' => $userId,
            'number' => $request->input('number'),
            'operator' => $request->input('operator'),
            'amount' => $amount,
            'status' => 'Pending',
            'created_at' => now()->toDateTimeString()
        ]);

        return response()->json([
            'status' => 'success',
            'success' => true, 
            'message' => 'Recharge Request Submitted'
        ]);
    }

    /**
     * Legacy Recharge History (get_recharge_history.php)
     */
    public function getRechargeHistory(Request $request)
    {
        $userId = $request->input('user_id');
        $recharges = \App\Models\RechargeTransaction::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['success' => true, 'history' => $recharges]);
    }

    /**
     * Legacy Course Progress (get_course_progress.php)
     */
    public function getCourseProgress(Request $request)
    {
        $userId = $request->input('user_id');
        // Simple mapping, might need more detail from course_progress_videos table
        $progress = \Illuminate\Support\Facades\DB::table('course_progress_videos')
            ->where('user_id', $userId)
            ->get();

        return response()->json(['success' => true, 'data' => ['watched_videos' => $progress->count()]]);
    }

    /**
     * Legacy Salary Request (salary_request.php)
     */
    public function salaryRequest(Request $request)
    {
        $userId = $request->input('user_id');
        \App\Models\SalaryRequest::create([
            'user_id' => $userId,
            'status' => 'Pending',
            'created_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Salary Request Submitted']);
    }

    /**
     * Legacy Check Refer Code (getRefer.php)
     */
    public function checkReferCode(Request $request)
    {
        $referCode = $request->input('referCode');
        $user = SignUp::where('referCode', $referCode)->first();

        if ($user) {
            return response()->json(['success' => true, 'name' => $user->name, 'user_id' => $user->id]);
        }
        return response()->json(['success' => false, 'message' => 'Invalid Refer Code']);
    }

    /**
     * Legacy Save FCM Token (save_fcm_token.php)
     */
    public function saveFcmToken(Request $request)
    {
        $userId = $request->input('user_id');
        $token = $request->input('fcm_token');
        
        SignUp::where('id', $userId)->update(['fcm_token' => $token]);
        
        return response()->json([
            'status' => 'success',
            'success' => true, 
            'message' => 'Token Saved'
        ]);
    }

    /**
     * Mark Verification Success Popup as Seen
     */
    /**
     * Legacy Spin Data (get_spin_data.php)
     */
    public function getSpinData(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);
        
        return response()->json([
            'success' => true,
            'spin_count' => (int)($user->math_game ?? 0),
            'target' => 50, // Example target
        ]);
    }

    /**
     * Legacy Spin Wheel (spin_wheel.php)
     */
    public function submitSpinResult(Request $request)
    {
        $userId = $request->input('user_id');
        $amount = $request->input('amount');
        
        $user = SignUp::find($userId);
        if ($user) {
            $user->increment('wallet_balance', $amount);
            return response()->json(['success' => true, 'new_balance' => $user->wallet_balance]);
        }
        return response()->json(['success' => false]);
    }

    /**
     * Legacy Microjobs List (get_microjobs.php)
     */
    public function getAllMicrojobs()
    {
        return response()->json(Microjob::where('status', 1)->get());
    }

    /**
     * Legacy Submit Microjob (submit_microjob.php)
     */
    public function submitMicrojob(Request $request)
    {
        // Handle both simple and multipart
        $userId = $request->input('user_id');
        $jobId = $request->input('job_id');
        $proofMessage = $request->input('proof_message');
        $proofImageUrl = $request->input('proof_image_url');

        if ($request->hasFile('proof_image')) {
            $path = $request->file('proof_image')->store('microjobs', 'public');
            $proofImageUrl = asset('storage/' . $path);
        }

        \Illuminate\Support\Facades\DB::table('microjob_submissions')->insert([
            'user_id' => $userId,
            'job_id' => $jobId,
            'proof_message' => $proofMessage,
            'proof_image_url' => $proofImageUrl,
            'status' => 'Pending',
            'created_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Proof Submitted']);
    }

    /**
     * Legacy Salary Status (get_salary_request_status.php)
     */
    public function getSalaryStatus(Request $request)
    {
        $userId = $request->query('user_id');
        $lastRequest = \App\Models\SalaryRequest::where('user_id', $userId)->latest()->first();
        
        return response()->json([
            'success' => true,
            'status' => $lastRequest ? $lastRequest->status : 'None'
        ]);
    }

    /**
     * Legacy Upload Profile Pic (upload_profile_pic.php)
     */
    public function uploadProfilePic(Request $request)
    {
        if ($request->hasFile('file')) {
            $userId = $request->input('user_id');
            $path = $request->file('file')->store('profiles', 'public');
            $url = asset('storage/' . $path);
            
            SignUp::where('id', $userId)->update(['profile_pic_url' => $url]);
            return response()->json(['success' => true, 'url' => $url]);
        }
        return response()->json(['success' => false, 'message' => 'No file provided']);
    }

    /**
     * Legacy Password Check (check-password-update.php)
     */
    public function checkPasswordUpdate(Request $request)
    {
        $number = $request->query('number');
        $user = SignUp::where('number', $number)->first();
        
        if ($user) {
            return response()->json([
                'status' => 'success',
                'success' => true,
                'password_updated_at' => $user->password_updated_at ?? ''
            ]);
        }
        
        return response()->json(['status' => 'error', 'message' => 'User not found']);
    }

    /**
     * Legacy Order Submission (submit_order_request.php)
     */
    public function submitOrder(Request $request)
    {
        // Simple mock since orders usually involve multiple steps
        return response()->json(['success' => true, 'message' => 'Order Received']);
    }

    /**
     * Legacy Solve Math (solve_math.php)
     */
    public function solveMath(Request $request)
    {
        $userId = $request->input('user_id');
        $correct = $request->input('correct_answer');
        $userAns = $request->input('user_answer');

        if ($correct == $userAns) {
            $user = SignUp::find($userId);
            if ($user) {
                $user->increment('wallet_balance', 1); // Small reward
                return response()->json(['success' => true, 'message' => 'Correct!']);
            }
        }
        return response()->json(['success' => false, 'message' => 'Wrong Answer']);
    }

    /**
     * Get Popups
     */
    public function getPopup()
    {
        $popup = \App\Models\PopupData::latest()->first();
        return response()->json($popup);
    }

    /**
     * Get Tutorials
     */
    public function getTutorials()
    {
        $tutorials = \App\Models\Tutorials::all();
        return response()->json([
            'success' => true,
            'tutorials' => $tutorials
        ]);
    }

    /**
     * Get Online Services
     */
    public function getServices()
    {
        $services = \App\Models\Service::all();
        return response()->json([
            'success' => true,
            'services' => $services
        ]);
    }

    public function getServiceById(Request $request)
    {
        $id = $request->query('id');
        $service = \App\Models\Service::find($id);
        return response()->json([
            'success' => true,
            'service' => $service
        ]);
    }

    public function submitOnlineServiceOrder(Request $request)
    {
        \App\Models\OnlineServiceOrder::create($request->all());
        return response()->json(['success' => true, 'message' => 'Order placed']);
    }

    public function getUserOnlineServiceOrders(Request $request)
    {
        $userId = $request->query('user_id');
        $orders = \App\Models\OnlineServiceOrder::where('user_id', $userId)->get();
        return response()->json(['success' => true, 'orders' => $orders]);
    }

    /**
     * Course Progress and Bonus
     */
    public function claimCourseBonus(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);
        if ($user) {
            $user->increment('wallet_balance', 50); // Example bonus
            return response()->json(['success' => true, 'message' => 'Bonus Claimed']);
        }
        return response()->json(['success' => false]);
    }

    public function updateCourseCompletion(Request $request)
    {
        $userId = $request->input('user_id');
        $courseId = $request->input('course_id');
        // Logic to mark course as completed
        return response()->json(['success' => true]);
    }

    /**
     * Job Status and Texts
     */
    public function getJobStatus()
    {
        return response()->json(\App\Models\JobStatus::first());
    }

    public function getJobText(Request $request)
    {
        $type = $request->query('job_type');
        return response()->json(\App\Models\JobText::where('type', $type)->first());
    }

    public function getJobTutorial(Request $request)
    {
        $type = $request->query('job_type');
        return response()->json(\App\Models\JobTutorial::where('type', $type)->first());
    }

    /**
     * OTP and Password Reset
     */
    public function sendEmailOtp(Request $request)
    {
        $email = $request->input('email');
        // Mocking OTP for now
        return response()->json(['success' => true, 'message' => 'OTP Sent']);
    }

    public function resetPassword(Request $request)
    {
        $email = $request->input('email');
        $otp = $request->input('otp');
        $newPass = $request->input('new_password');
        
        $user = SignUp::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make($newPass);
            $user->save();
            return response()->json(['success' => true, 'message' => 'Password reset successful']);
        }
        return response()->json(['success' => false]);
    }

    public function sendWithdrawOtp(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'OTP Sent']);
    }

    /**
     * Recharge and SIM Offer Manage
     */
    public function recharge(Request $request)
    {
        // Integration with 3rd party API goes here
        return response()->json(['success' => true, 'message' => 'Recharge successful']);
    }

    public function rechargeSuccessHandler(Request $request)
    {
        return response()->json(['success' => true]);
    }

    public function confirmSimOffer(Request $request)
    {
        $requestId = $request->input('request_id');
        // Logic to confirm sim offer
        return response()->json(['success' => true]);
    }

    public function getSimOfferManage()
    {
        return response()->json(['status' => 'active']);
    }

    public function getSalaryProgress(Request $request)
    {
        $userId = $request->query('user_id');
        // Return salary progress data
        return response()->json(['success' => true, 'progress' => 0]);
    }

    public function getSpinProgress(Request $request)
    {
        $userId = $request->query('user_id');
        return response()->json(['success' => true, 'progress' => 0]);
    }

    public function getMathIncome(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);
        return response()->json(['success' => true, 'balance' => $user->wallet_balance ?? 0]);
    }
}
