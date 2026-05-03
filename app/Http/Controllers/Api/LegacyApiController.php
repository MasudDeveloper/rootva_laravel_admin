<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\SocialLink;
use App\Models\PaymentNumber;
use App\Models\AppUpdate;
use App\Models\Banner;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\Microjob;
use App\Models\MicrojobSubmission;
use App\Models\MoneyRequest;
use App\Models\WithdrawRequest;
use App\Models\VerificationRequest;
use App\Models\CourseVideo;
use App\Models\CourseProgress;
use App\Models\SalaryRequest;
use App\Models\SpinHistory;
use App\Models\WheelSpinInfo;
use App\Models\SimOfferRequest;
use App\Models\PopupBanner;
use App\Models\Service;
use App\Models\OnlineServiceOrder;
use App\Models\Order;
use App\Models\IncomingPaymentSms;
use App\Models\BonusTracker;
use App\Models\LeadershipClaim;
use App\Models\LeadershipRewardRequest;
use App\Models\RechargeTransaction;
use App\Models\SimOffer;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            if (password_verify($password, $user->password)) {
                $token = bin2hex(random_bytes(32));
                $user->update(['api_token' => $token]);
                
                return response()->json([
                    'message' => 'লগইন সফল',
                    'userId' => $user->id,
                    'password_updated_at' => $user->password_updated_at ?? '',
                    'api_token' => $token
                ]);
            } else {
                return response()->json(['message' => 'ভুল পাসওয়ার্ড']);
            }
        } else {
            return response()->json(['message' => 'নম্বরটি পাওয়া যায়নি']);
        }
    }

    /**
     * Legacy User Data (get_Data.php)
     */
    public function getUserData(Request $request)
    {
        $number = $request->input('number');
        
        if (empty($number)) {
            return response()->json(['status' => 'error', 'message' => 'অবৈধ ডেটা']);
        }

        $user = SignUp::where('number', $number)->first();
        if ($user) {
            return response()->json([
                'status' => 'success',
                'message' => 'ইউজার তথ্য সফলভাবে পাওয়া গেছে',
                'users' => $user,
                'show_verification_popup' => (bool)($user->is_verified == 1 && $user->verification_popup_shown == 0)
            ]);
        }
        return response()->json(['status' => 'error', 'message' => 'ইউজার পাওয়া যায়নি']);
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

        if (!$user) {
            return response()->json(['message' => 'অবৈধ ডেটা'], 200);
        }

        // Calculate total balance from transactions
        // Credit types: add, commission, income
        $credits = \App\Models\Transaction::where('user_id', $userId)
            ->whereIn('type', ['add', 'commission', 'income'])
            ->sum('amount');

        // Debit types: withdraw, payment
        $debits = \App\Models\Transaction::where('user_id', $userId)
            ->whereIn('type', ['withdraw', 'payment'])
            ->sum('amount');

        $totalBalance = $credits - $debits;

        // Update wallet balance in the database
        $user->wallet_balance = $totalBalance;
        $user->save();

        return response()->json([
            'message' => 'ব্যালেন্স সফলভাবে আপডেট হয়েছে',
            'wallet_balance' => round((double)$totalBalance, 2),
            'voucher_balance' => round((double)($user->voucher_balance ?? 0), 2)
        ]);
    }

    /**
     * Legacy Transaction History (get_transaction_history.php)
     */
    public function getTransactionHistory(Request $request)
    {
        $user_id = $request->query('user_id') ?? $request->input('user_id');

        if ($user_id) {
            $transactions = Transaction::where('user_id', $user_id)
                ->where(function ($query) {
                    $query->whereNotIn('type', ['withdraw', 'voucher_withdraw'])
                        ->orWhere(function ($q) {
                            $q->whereIn('type', ['withdraw', 'voucher_withdraw'])
                                ->where('description', 'Withdraw Request Approved');
                        });
                })
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get();

            if ($transactions->isNotEmpty()) {
                return response()->json([
                    'transactions' => $transactions
                ]);
            } else {
                return response()->json([
                    'message' => 'কোনো ট্রানজেকশন পাওয়া যায়নি'
                ]);
            }
        } else {
            return response()->json([
                'message' => 'অবৈধ ডেটা'
            ]);
        }
    }

    /**
     * Legacy Income Report (get_income_report.php)
     */
    public function getIncomeReport(Request $request)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data',
                'data' => null
            ]);
        }

        $calculate = function($startDate = null, $endDate = null) use ($userId) {
            $query = \App\Models\Transaction::where('user_id', $userId)
                ->whereIn('type', ['income', 'commission']);

            if ($startDate && $endDate) {
                $query->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(date)'), [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->whereDate('date', $startDate);
            }

            $total = $query->sum('amount');
            return [
                'total' => number_format((float)$total, 2, '.', ''),
                'details' => []
            ];
        };

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $last7Days = now()->subDays(6)->toDateString();
        $last30Days = now()->subDays(29)->toDateString();
        $last365Days = now()->subDays(364)->toDateString();

        return response()->json([
            'status' => 'success',
            'message' => 'Income report fetched successfully',
            'data' => [
                'today' => $calculate($today),
                'yesterday' => $calculate($yesterday),
                'week' => $calculate($last7Days, $today),
                'month' => $calculate($last30Days, $today),
                'year' => $calculate($last365Days, $today),
                'total' => $calculate()
            ]
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Legacy Income History (get_income_history.php)
     */
    public function getIncomeHistory(Request $request)
    {
        $user_id = $request->query('user_id') ?? $request->input('user_id');

        if ($user_id) {
            $transactions = \App\Models\Transaction::where('user_id', $user_id)
                ->whereIn('type', ['income', 'commission'])
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Income report fetched successfully',
                'income_history' => $transactions
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data',
                'income_history' => null
            ]);
        }
    }

    /**
     * Legacy Banners (get_banners.php)
     */
    public function getBanners()
    {
        return response()->json(Banner::orderBy('created_at', 'desc')->get());
    }

    /**
     * Legacy Social Links (get_social_links.php)
     */
    public function getSocialLinks()
    {
        $links = SocialLink::orderBy('id', 'desc')->first();
        
        if ($links) {
            return response()->json([
                'status' => true,
                'message' => 'লিংক সফলভাবে পাওয়া গেছে',
                'socialLinks' => $links
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'লিংক পাওয়া যায়নি'
            ]);
        }
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
        return response()->json(SimOffer::orderBy('id', 'desc')->get());
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
                'users' => $user,
                'message' => "ডেটা সফলভাবে লোড হয়েছে"
            ]);
        }
        
        return response()->json([
            'message' => "ইউজার খুঁজে পাওয়া যায়নি"
        ]);
    }

    /**
     * Legacy Withdraw Requests (get_withdraw_request.php)
     */
    public function getWithdrawRequests(Request $request)
    {
        $user_id = $request->query('user_id') ?? $request->input('user_id');
        
        if ($user_id) {
            $requests = \App\Models\WithdrawRequest::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get();
                
            return response()->json($requests);
        }
        
        return response()->json([]);
    }

    /**
     * Legacy Money Requests (get_money_requests.php)
     */
    public function getMoneyRequests(Request $request)
    {
        $user_id = $request->query('user_id') ?? $request->input('user_id');
        
        if ($user_id) {
            $requests = \App\Models\MoneyRequest::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get();
                
            return response()->json($requests);
        }
        
        return response()->json([]);
    }


    /**
     * Legacy Profile Update (update_profile.php)
     */
    public function updateProfile(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Update Profile Request:', [
            'all' => $request->all(),
            'user_id' => $request->input('user_id'),
            'post_user_id' => $_POST['user_id'] ?? 'not_in_post'
        ]);

        $user_id = $request->input('user_id') ?? ($_POST['user_id'] ?? null);
        $name = $request->input('name');
        $gender = $request->input('gender');
        $address = $request->input('address');
        $email = $request->input('email');
        $profile_pic_url = $request->input('profile_pic_url');

        if ($user_id) {
            $user = SignUp::find($user_id);
            if ($user) {
                $updated = $user->update([
                    'name'            => $name,
                    'gender'          => $gender,
                    'address'         => $address,
                    'email'           => $email,
                    'profile_pic_url' => $profile_pic_url,
                ]);

                if ($updated) {
                    return response()->json(['message' => 'প্রোফাইল আপডেট সফল']);
                } else {
                    return response()->json(['message' => 'প্রোফাইল আপডেট ব্যর্থ']);
                }
            }
        }
        
        return response()->json(['message' => 'অবৈধ ডেটা']);
    }

    /**
     * Legacy Payment Numbers (get_payment_numbers.php)
     */
    public function getPaymentNumbers()
    {
        $numbers = PaymentNumber::orderBy('id', 'desc')->first();
        if (!$numbers) {
            return response()->json([
                'error' => true,
                'message' => 'No payment number found'
            ]);
        }
        
        return response()->json([
            'error' => false,
            'bkash' => $numbers->bkash,
            'nagad' => $numbers->nagad,
            'rocket' => $numbers->rocket,
            'upay' => $numbers->upay,
            'verify_amount' => $numbers->verify_amount
        ]);
    }

    /**
     * Legacy Reviews (get_reviews.php)
     */
    public function getReviews()
    {
        $reviews = Review::orderBy('created_at', 'desc')->get();
        return response()->json($reviews);
    }

    /**
     * Legacy Microjobs (get_microjobs2.php)
     */
    /**
     * Legacy Add Money (add_money_request.php)
     */
    public function addMoneyRequest(Request $request)
    {
        $user_id = trim($request->input('user_id', ''));
        $name = trim($request->input('title', '')); // Using 'title' as name/title
        $account_number = trim($request->input('account_number', ''));
        $amount = trim($request->input('amount', ''));
        $transaction_id = trim($request->input('transaction_id', ''));
        $payment_gateway = trim($request->input('payment_gateway', ''));
        $current_time = $request->input('current_time', now()->format('Y-m-d H:i:s'));

        // ভ্যালিডেশন
        if (empty($user_id) || empty($amount) || empty($payment_gateway)) {
            return response()->json([
                'status' => 'error',
                'message' => "সবগুলো ফিল্ড পূরণ করুন"
            ]);
        }

        if (!is_numeric($amount)) {
            return response()->json([
                'status' => 'error',
                'message' => "অবৈধ অ্যামাউন্ট"
            ]);
        }

        try {
            \App\Models\MoneyRequest::create([
                'user_id' => $user_id,
                'title' => $name,
                'account_number' => $account_number,
                'amount' => $amount,
                'transaction_id' => $transaction_id,
                'payment_gateway' => $payment_gateway,
                'created_at' => $current_time
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "রিকোয়েস্ট সফলভাবে সাবমিট হয়েছে"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => "রিকোয়েস্ট সাবমিট করতে ব্যর্থ"
            ]);
        }
    }

    /**
     * Legacy Withdraw (submit_money_withdraw_request.php)
     */
    public function withdrawRequest(Request $request)
    {
        $user_id = $request->input('user_id');
        $amount = $request->input('amount');
        $payment_gateway = trim($request->input('payment_gateway', ''));
        $account_number = trim($request->input('account_number', ''));
        $balance_type = trim($request->input('balance_type', 'wallet')); // "wallet" or "voucher"
        $current_time = now()->format('Y-m-d H:i:s');

        if (!$user_id || !$amount || !$payment_gateway || !$account_number) {
            return response()->json(['status' => 'error', 'message' => "অবৈধ ডেটা"]);
        }

        if (!is_numeric($amount) || $amount < 250) {
            return response()->json(['status' => 'error', 'message' => "নূন্যতম ২৫০ টাকা তুলতে পারবেন"]);
        }

        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => "ইউজার পাওয়া যায়নি"]);
        }

        $wallet_balance = (float)$user->wallet_balance;
        $voucher_balance = (float)$user->voucher_balance;

        // ফি ক্যালকুলেশন (2%)
        $fee = round($amount * 0.02, 2);
        $net_amount = round($amount - $fee, 2);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // ব্যালেন্স টাইপ অনুযায়ী চেক করো
            if ($balance_type === 'wallet') {
                if ($wallet_balance < $amount) {
                    return response()->json(['status' => 'error', 'message' => "আপনার ওয়ালেট ব্যালেন্স যথেষ্ট নয়"]);
                }
                $user->update(['wallet_balance' => $wallet_balance - $amount]);
            } elseif ($balance_type === 'voucher') {
                if ($voucher_balance < $amount) {
                    return response()->json(['status' => 'error', 'message' => "আপনার ভাউচার ব্যালেন্স যথেষ্ট নয়"]);
                }
                $user->update(['voucher_balance' => $voucher_balance - $amount]);
            } else {
                return response()->json(['status' => 'error', 'message' => "অবৈধ ব্যালেন্স টাইপ"]);
            }

            // Withdraw Request Entry
            \App\Models\WithdrawRequest::create([
                'user_id' => $user_id,
                'amount' => $amount,
                'net_amount' => $net_amount,
                'fee' => $fee,
                'payment_gateway' => $payment_gateway,
                'account_number' => $account_number,
                'balance_type' => $balance_type,
                'status' => 'Pending',
                'created_at' => $current_time
            ]);

            // Transaction Log Entry
            $description = ucfirst($balance_type) . " Withdraw Request Pending";
            $transaction_type = ($balance_type === 'wallet') ? 'withdraw' : 'voucher_withdraw';

            \App\Models\Transaction::create([
                'user_id' => $user_id,
                'amount' => $amount,
                'payment_gateway' => $payment_gateway,
                'type' => $transaction_type,
                'description' => $description,
                'update_at' => $current_time,
                'created_at' => $current_time,
                'date' => $current_time
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => ucfirst($balance_type) . " থেকে রিকোয়েস্ট সফলভাবে সাবমিট হয়েছে",
                'fee' => $fee,
                'net_amount' => $net_amount
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['status' => 'error', 'message' => "রিকোয়েস্ট সাবমিট করতে ব্যর্থ"]);
        }
    }

    /**
     * Legacy Verification (submit_verification_request.php)
     */
    public function submitVerificationRequest(Request $request)
    {
        $user_id = $request->input('user_id');
        $refer_id = $request->input('refer_id');
        $account_number = $request->input('account_number');
        $transaction_id = $request->input('transaction_id');
        $amount = $request->input('amount');
        $payment_gateway = $request->input('payment_gateway');
        $current_time = $request->input('current_time', now()->toDateTimeString());

        if (!$user_id || !$account_number || !$transaction_id || !$amount) {
            return response()->json(['message' => 'অবৈধ ডেটা']);
        }

        // 1. Check if this transaction_id is already used in an approved request
        $existingApproved = \App\Models\VerificationRequest::where('transaction_id', $transaction_id)
            ->where('status', 'Approved')
            ->first();

        if ($existingApproved) {
            return response()->json([
                'message' => "⚠️ এই ট্রানজেকশন আইডি ইতোমধ্যে ব্যবহৃত হয়েছে। অনুগ্রহ করে একটি বৈধ ট্রানজেকশন আইডি দিন।"
            ]);
        }

        // 2. Check user verification status
        $user = SignUp::find($user_id);

        if (!$user) {
            return response()->json(['message' => "ইউজার খুঁজে পাওয়া যায়নি"]);
        }

        if ($user->is_verified != 0) {
            return response()->json(['message' => "ইউজার ইতিমধ্যে ভেরিফাইড বা রিকোয়েস্ট পেন্ডিং আছে"]);
        }

        // 3. Update sign_up status to pending (2)
        $user->is_verified = 2;
        $user->save();

        // 4. Insert verification request
        try {
            \App\Models\VerificationRequest::create([
                'user_id' => $user_id,
                'refer_id' => $refer_id ?? '',
                'account_number' => $account_number,
                'transaction_id' => $transaction_id,
                'amount' => $amount,
                'payment_gateway' => $payment_gateway,
                'status' => 'Pending',
                'created_at' => $current_time,
                'updated_at' => now()->toDateTimeString()
            ]);

            return response()->json([
                'message' => "ভেরিফিকেশন রিকোয়েস্ট সফলভাবে সাবমিট হয়েছে"
            ]);
        } catch (\Exception $e) {
            // Restore status if failed
            $user->is_verified = 0;
            $user->save();

            if ($e->getCode() == 23000) { // Integrity constraint violation (likely duplicate)
                return response()->json([
                    'message' => "আপনার একটি ভেরিফিকেশন রিকোয়েস্ট ইতিমধ্যে পেন্ডিং আছে বা ট্রানজেকশন আইডি ব্যবহৃত হয়েছে!"
                ]);
            }

            return response()->json([
                'message' => "Server error: " . $e->getMessage()
            ]);
        }
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
     * Legacy Course List (get_courses.php)
     */
    public function getAllVideos(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID missing',
                'videos' => []
            ]);
        }

        // course_videos থেকে সব ভিডিও নেওয়া
        $videos = \App\Models\CourseVideo::orderBy('created_at', 'ASC')->get();

        if ($videos->count() > 0) {
            $formattedVideos = [];

            foreach ($videos as $video) {
                // প্রতিটি ভিডিওর জন্য ইউজারের প্রগ্রেস fetch করা
                $progress = \DB::table('course_progress_videos')
                    ->where('user_id', $userId)
                    ->where('video_id', $video->id)
                    ->first();

                $formattedVideos[] = [
                    'id' => (int)$video->id,
                    'title' => $video->title,
                    'youtube_url' => $video->youtube_url,
                    'duration' => $video->duration,
                    'created_at' => $video->created_at,
                    'progress' => [
                        'is_complete' => $progress ? (int)$progress->is_complete : 0,
                        'watched_seconds' => $progress ? (int)$progress->watched_seconds : 0
                    ]
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Videos fetched successfully',
                'videos' => $formattedVideos
            ]);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'No videos found',
                'videos' => []
            ]);
        }
    }

    /**
     * Legacy Course Progress (get_course_progress.php)
     */
    public function getCourseProgress(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                "success" => false,
                "message" => "Missing user_id"
            ]);
        }

        // মোট ভিডিও সংখ্যা
        $totalVideos = \App\Models\CourseVideo::count();

        // ইউজারের প্রগ্রেস
        $progressData = \Illuminate\Support\Facades\DB::table('course_progress_videos')
            ->where('user_id', $userId)
            ->selectRaw('COUNT(*) as watched_count, SUM(is_complete) as complete_count')
            ->first();

        $watched_count = $progressData ? (int)$progressData->watched_count : 0;
        $complete_count = $progressData ? (int)$progressData->complete_count : 0;

        // প্রগ্রেস percentage হিসাব (পুরো ভিডিও দেখার ভিত্তিতে)
        $progress_percent = 0;
        if ($totalVideos > 0) {
            $progress_percent = round(($complete_count / $totalVideos) * 100);
        }

        $progress = [
            "total_videos" => (int)$totalVideos,
            "watched_videos" => $watched_count,
            "completed_videos" => $complete_count,
            "progress_percent" => $progress_percent
        ];

        return response()->json([
            "success" => true,
            "message" => "Course progress fetched successfully",
            "progress" => $progress
        ]);
    }

    /**
     * Update Course Progress (update_course_progress.php)
     */
    public function updateCourseProgress(Request $request)
    {
        $user_id = intval($request->input('user_id', 0));
        $video_id = intval($request->input('video_id', 0));
        $watched_seconds = intval($request->input('watched_seconds', 0));

        if ($user_id <= 0 || $video_id <= 0 || $watched_seconds < 0) {
            return response()->json(['error' => true, 'message' => 'Missing or invalid parameters']);
        }

        // ✅ ভিডিওর duration বের করা
        $video = \App\Models\CourseVideo::find($video_id);

        if (!$video) {
            return response()->json(['error' => true, 'message' => 'Video not found']);
        }

        // Duration string থেকে seconds এ কনভার্ট করা
        $total_duration = $this->durationToSeconds($video->duration);

        // ✅ চেক করা ভিডিও সম্পূর্ণ দেখা হয়েছে কিনা
        $is_complete = ($watched_seconds >= $total_duration) ? 1 : 0;

        // ✅ Progress আপডেট বা ক্রিয়েট করা
        \Illuminate\Support\Facades\DB::table('course_progress_videos')->updateOrInsert(
            ['user_id' => $user_id, 'video_id' => $video_id],
            [
                'watched_seconds' => $watched_seconds,
                'is_complete' => $is_complete,
                'updated_at' => now()
            ]
        );

        return response()->json([
            'error' => false,
            'message' => 'Progress updated successfully',
            'is_complete' => (int)$is_complete,
            'watched_seconds' => (int)$watched_seconds,
            'total_duration' => (int)$total_duration
        ]);
    }

    private function durationToSeconds($duration)
    {
        $parts = explode(':', $duration);
        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        } elseif (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        } else {
            return intval($duration);
        }
    }

    /**
     * Legacy Salary Request (salary_request.php)
     */
    public function salaryRequest(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'User ID is missing']);
            }

            // Check for existing pending request
            $existing = \App\Models\SalaryRequest::where('user_id', $userId)
                ->where('status', 'Pending')
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false, 
                    'message' => 'আপনার একটি রিকোয়েস্ট অলরেডি পেন্ডিং আছে।'
                ], 200);
            }

            \App\Models\SalaryRequest::create([
                'user_id' => $userId,
                'status' => 'Pending',
                'request_type' => 'monthly_salary',
                'requested_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'success' => true, 
                'message' => 'Salary Request Submitted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ], 200); // We return 200 so the app shows the actual message
        }
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
        
        if (!$userId || !$token) {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }

        SignUp::where('id', $userId)->update(['fcm_token' => $token]);
        
        return response()->json([
            'status' => true,
            'message' => 'FCM Token updated'
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
        $user_id = intval($request->input('user_id', 0));

        if ($user_id <= 0) {
            return response()->json(['error' => true, 'message' => 'Invalid user ID']);
        }

        try {
            return \DB::transaction(function () use ($user_id) {
                // Check if user exists in wheel_spin_info
                $info = \App\Models\WheelSpinInfo::where('user_id', $user_id)->first();

                if (!$info) {
                    // Create new record for new user
                    $current_time = now();
                    $info = \App\Models\WheelSpinInfo::create([
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
            return \DB::transaction(function () use ($user_id, $amount) {
                // Check user exists
                $user = \App\Models\SignUp::find($user_id);
                if (!$user) {
                    return response()->json(['error' => true, 'message' => 'User not found']);
                }

                // Get or insert wheel_spin_info
                $info = \App\Models\WheelSpinInfo::where('user_id', $user_id)->lockForUpdate()->first();
                
                if (!$info) {
                    $info = \App\Models\WheelSpinInfo::create([
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
                    $isVerified = \DB::table('verification_requests')
                        ->where('user_id', $user_id)
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
                    \App\Models\SpinHistory::create([
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
     * Legacy Microjobs List (get_microjobs.php)
     */
    public function getAllMicrojobs(Request $request)
    {
        $user_id = $request->input('user_id') ?? $request->query('user_id');

        if (empty($user_id)) {
            return response()->json(['success' => false, 'message' => 'User ID not provided']);
        }

        // Get job IDs already submitted by this user
        $submittedJobIds = \Illuminate\Support\Facades\DB::table('microjob_submissions')
            ->where('worker_user_id', $user_id)
            ->pluck('job_id');

        $jobs = Microjob::where('remaining_target', '>', 0)
            ->where('is_active', 1)
            ->where('status', 'approved')
            ->whereNotIn('id', $submittedJobIds)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($jobs);
    }

    /**
     * Legacy Submit Microjob (submit_microjob.php)
     */
    public function submitMicrojob(Request $request)
    {
        $job_id = $request->input('job_id');
        $worker_user_id = $request->input('user_id');
        $proof_message = $request->input('message');
        $date = now()->toDateTimeString();

        if (!$job_id || !$worker_user_id || !$proof_message) {
            return response()->json(["success" => false, "message" => "Required parameters missing."]);
        }

        // check if already submitted
        $check = \Illuminate\Support\Facades\DB::table('microjob_submissions')
            ->where('job_id', $job_id)
            ->where('worker_user_id', $worker_user_id)
            ->exists();

        if ($check) {
            return response()->json(["success" => false, "message" => "You already submitted proof for this job."]);
        }

        // check job target
        $job = \Illuminate\Support\Facades\DB::table('microjobs')->where('id', $job_id)->first();
        if (!$job || $job->remaining_target <= 0) {
            return response()->json(["success" => false, "message" => "Job target reached!"]);
        }

        $proof_image = '';
        if ($request->hasFile('proof_image')) {
            $file = $request->file('proof_image');
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!in_array($file->getMimeType(), $allowed_types)) {
                return response()->json(["success" => false, "message" => "Invalid file type. Only JPG, PNG, and GIF are allowed."]);
            }

            // Generate unique file name
            $proof_image = time() . '_' . $file->getClientOriginalName();

            // Move the uploaded file to 'ProofImage' folder in public
            $file->move(public_path('ProofImage'), $proof_image);
        }

        // insert submission
        $submitted = \Illuminate\Support\Facades\DB::table('microjob_submissions')->insert([
            'job_id' => $job_id,
            'worker_user_id' => $worker_user_id,
            'proof_message' => $proof_message,
            'proof_image' => $proof_image,
            'status' => 'pending',
            'created_at' => $date
        ]);

        if ($submitted) {
            // decrease job target
            \Illuminate\Support\Facades\DB::table('microjobs')->where('id', $job_id)->decrement('remaining_target');
            return response()->json(["success" => true, "message" => "Proof submitted successfully!"]);
        } else {
            return response()->json(["success" => false, "message" => "Failed to submit proof."]);
        }
    }

    /**
     * Legacy Salary Status (get_salary_request_status.php)
     */
    public function getSalaryStatus(Request $request)
    {
        $userId = $request->query('user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required',
                'status' => 'None',
                'admin_note' => null
            ]);
        }

        $lastRequest = \App\Models\SalaryRequest::where('user_id', $userId)
            ->latest('requested_at')
            ->first();
        
        return response()->json([
            'success' => true,
            'status' => $lastRequest ? $lastRequest->status : 'None',
            'admin_note' => $lastRequest ? $lastRequest->admin_note : null
        ]);
    }

    /**
     * Legacy Upload Profile Pic (upload_profile_pic.php)
     */
    public function uploadProfilePic(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Upload Profile Pic Request:', [
            'all' => $request->all(),
            'user_id' => $request->input('user_id'),
            'post_user_id' => $_POST['user_id'] ?? null,
            'has_file' => $request->hasFile('file'),
            'files' => $_FILES
        ]);
        
        $user_id = $request->input('user_id') ?? ($_POST['user_id'] ?? null);
        $file = $request->file('file');

        if ($user_id && $file) {
            $targetDir = "Images/";
            $publicPath = public_path($targetDir);
            if (!is_dir($publicPath)) {
                mkdir($publicPath, 0777, true);
            }

            // পুরনো ছবি ডিলিট করার লজিক (আপনার পিএইচপি কোড অনুযায়ী)
            $user = SignUp::find($user_id);
            if ($user && !empty($user->profile_pic_url)) {
                $oldFilePath = parse_url($user->profile_pic_url, PHP_URL_PATH);
                $oldFilePath = ltrim($oldFilePath, '/');
                $fullOldPath = public_path($oldFilePath);
                if (file_exists($fullOldPath)) {
                    unlink($fullOldPath);
                }
            }

            // ফাইল ফরম্যাট চেক
            $fileExtension = $file->getClientOriginalExtension();
            $allowTypes = ['jpg', 'png', 'jpeg'];
            
            if (in_array(strtolower($fileExtension), $allowTypes)) {
                $fileName = uniqid('profile_', true) . "." . $fileExtension;
                
                try {
                    $file->move($publicPath, $fileName);
                    $profilePicUrl = "https://api.rootvabd.com/" . $targetDir . $fileName;
                    
                    if ($user) {
                        $user->update(['profile_pic_url' => $profilePicUrl]);
                        return response()->json(['message' => $profilePicUrl]);
                    } else {
                        return response()->json(['message' => 'ডেটা আপডেট ব্যর্থ']);
                    }
                } catch (\Exception $e) {
                    return response()->json(['message' => 'ছবি আপলোড ব্যর্থ']);
                }
            } else {
                return response()->json(['message' => 'অনুমোদিত ফাইল ফরম্যাট নয়']);
            }
        }
        
        return response()->json(['message' => 'অবৈধ ডেটা']);
    }

    /**
     * Legacy Password Check (check-password-update.php)
     */
    public function checkPasswordUpdate(Request $request)
    {
        $number = $request->query('number');
        
        if (!$number) {
            return response()->json(['error' => 'Number is required']);
        }

        $user = SignUp::where('number', $number)->first();
        
        if ($user) {
            return response()->json([
                'password_updated_at' => $user->password_updated_at ?? ''
            ]);
        }
        
        return response()->json(['error' => 'Number not found']);
    }

    /**
     * Legacy Order Submission (submit_order_request.php)
     */
    public function submitOrder(Request $request)
    {
        $user_id = intval($request->input('user_id', 0));
        $product_id = intval($request->input('product_id', 0));
        $product_name = trim($request->input('product_name', ''));
        $product_price = floatval($request->input('product_price', 0));
        $quantity = intval($request->input('quantity', 0));
        $total_price = floatval($request->input('total_price', 0));
        $total_earning = floatval($request->input('total_earning', 0));
        $total_product_price = floatval($request->input('total_product_price', 0));
        $delivery_charge = floatval($request->input('delivery_charge', 0));
        $customer_name = trim($request->input('customer_name', ''));
        $customer_number = trim($request->input('customer_number', ''));
        $customer_address = trim($request->input('customer_address', ''));
        $account_number = trim($request->input('account_number', ''));
        $transaction_id = intval($request->input('transaction_id', 0));
        $amount = floatval($request->input('amount', 0));
        $payment_gateway = trim($request->input('payment_gateway', ''));

        // Basic validation
        if (
            $user_id <= 0 || 
            $product_id <= 0 || 
            empty($product_name) || 
            $product_price <= 0 || 
            empty($customer_name) || 
            empty($customer_number)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Required fields are missing or invalid.'
            ]);
        }

        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        if ($user->voucher_balance < $delivery_charge) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance']);
        }

        DB::beginTransaction();
        try {
            // Create Order
            $order = Order::create([
                'user_id' => $user_id,
                'product_id' => $product_id,
                'product_name' => $product_name,
                'product_price' => $product_price,
                'customer_name' => $customer_name,
                'customer_number' => $customer_number,
                'quantity' => $quantity,
                'total_price' => $total_price,
                'total_earning' => $total_earning,
                'total_product_price' => $total_product_price,
                'delivery_charge' => $delivery_charge,
                'customer_address' => $customer_address,
                'account_number' => $account_number,
                'transaction_id' => $transaction_id,
                'payment_gateway' => $payment_gateway,
                'amount' => $amount,
                'created_at' => date('Y-m-d H:i:s'),
                'order_status' => 'Pending'
            ]);

            // Create Transaction
            $currentTime2 = date('d-m-Y h:i A');
            $transaction = Transaction::create([
                'user_id' => $user_id,
                'refer_id' => $user->referCode,
                'amount' => $delivery_charge,
                'payment_gateway' => 'Reselling Advance',
                'type' => 'voucher_payment',
                'description' => 'Payment For Reselling',
                'update_at' => $currentTime2,
                'created_at' => date('Y-m-d H:i:s'), // Added missing created_at
                'date' => date('Y-m-d H:i:s')
            ]);

            Log::info('Transaction created', ['id' => $transaction->id]);

            // Deduct Balance
            $user->decrement('voucher_balance', $delivery_charge);

            // Update Order with Transaction ID
            $order->update(['transaction_id' => $transaction->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order submitted successfully.',
                'order_id' => $order->id,
                'transaction_id' => $transaction->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order Submission Error: ' . $e->getMessage(), [
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit order.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Legacy User Orders (get_user_orders.php)
     */
    public function getUserOrders(Request $request)
    {
        $userId = $request->query('user_id');
        
        if (!$userId || $userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid user ID']);
        }

        $orders = Order::where('user_id', $userId)
            ->select('id', 'product_name', 'product_price', 'order_status', 'cancel_reason', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Legacy Solve Math (solve_math.php)
     */
    public function submitMathAnswer(Request $request)
    {
        $userId = $request->input('user_id');
        $correctAnswer = $request->input('correct_answer');
        $userAnswer = $request->input('user_answer');

        $user = SignUp::find($userId);

        if ($user && $user->math_game > 0) {
            $isCorrect = ($correctAnswer == $userAnswer);

            return \DB::transaction(function () use ($user, $isCorrect) {
                // 1. Reduce math_game count
                $user->decrement('math_game');

                if ($isCorrect) {
                    // 2. Add 1 tk (following message and transaction log logic)
                    $user->increment('wallet_balance', 1);

                    // 3. Log transaction
                    $currentTime = date("d-m-Y h:i A");
                    $now = now();

                    \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'refer_id' => '',
                        'amount' => 1.00,
                        'type' => 'income',
                        'payment_gateway' => 'Typing Job',
                        'description' => 'Correct answer reward',
                        'update_at' => $currentTime,
                        'created_at' => $currentTime,
                        'date' => $now
                    ]);

                    return response()->json(["status" => "correct", "message" => "সঠিক উত্তর! 1 টাকা পেয়েছেন।"]);
                } else {
                    return response()->json(["status" => "wrong", "message" => "ভুল উত্তর। সুযোগ নষ্ট হয়েছে।"]);
                }
            });
        } else {
            return response()->json(["status" => "error", "message" => "আপনার math_game সুযোগ নেই।"]);
        }
    }

    /**
     * Get Popups
     */
    public function getPopupData()
    {
        $popup = \App\Models\PopupBanner::orderBy('id', 'desc')->first();

        if ($popup) {
            return response()->json([
                'success' => true,
                'image_url' => $popup->image_url,
                'message' => $popup->message,
                'button_text' => $popup->button_text,
                'button_url' => $popup->button_url
            ]);
        } else {
            return response()->json(['success' => false]);
        }
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
    public function getOnlineServices()
    {
        $services = \App\Models\Service::orderBy('id', 'DESC')->get();
        return response()->json([
            'error' => false,
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
        $user_id = intval($request->input('user_id'));
        $service_id = intval($request->input('service_id'));
        $whatsapp = trim($request->input('whatsapp'));
        $telegram = trim($request->input('telegram'));
        $price = floatval($request->input('price'));

        if (!$user_id || !$service_id || !$whatsapp || !$telegram) {
            return response()->json(['success' => false, 'message' => 'Missing fields']);
        }

        try {
            return \DB::transaction(function () use ($user_id, $service_id, $whatsapp, $telegram, $price) {
                // ✅ ইউজারের ব্যালেন্স চেক করা
                $user = \App\Models\SignUp::find($user_id);

                if (!$user) {
                    return response()->json(['success' => false, 'message' => 'User not found']);
                }

                if ($user->voucher_balance < $price) {
                    return response()->json(['success' => false, 'message' => 'Insufficient balance']);
                }

                // ✅ ব্যালেন্স কমানো
                $user->decrement('voucher_balance', $price);

                // ✅ রিকোয়েস্ট সেভ করা
                \App\Models\OnlineServiceOrder::create([
                    'user_id' => $user_id,
                    'service_id' => $service_id,
                    'whatsapp' => $whatsapp,
                    'telegram' => $telegram,
                    'status' => 'pending',
                    'created_at' => now()
                ]);

                return response()->json(["error" => false, "message" => "Order placed successfully"]);
            });
        } catch (\Exception $e) {
            return response()->json(["error" => true, "message" => "Database error: " . $e->getMessage()]);
        }
    }

    public function getUserOnlineServiceOrders(Request $request)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            return response()->json(['error' => true, 'message' => 'Invalid user ID']);
        }

        $orders = \DB::table('online_service_orders as oso')
            ->join('services as os', 'oso.service_id', '=', 'os.id')
            ->select('oso.*', 'os.name as service_name', 'os.price')
            ->where('oso.user_id', $userId)
            ->orderBy('oso.id', 'DESC')
            ->get();

        return response()->json([
            'error' => false,
            'orders' => $orders
        ]);
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
            return response()->json([
                'success' => true, 
                'message' => 'বোনাস সফলভাবে আপনার ওয়ালেটে যোগ করা হয়েছে'
            ]);
        }
        return response()->json(['success' => false, 'message' => 'ইউজার পাওয়া যায়নি']);
    }

    public function updateCourseCompletion(Request $request)
    {
        $user_id = intval($request->input('user_id', 0));
        $course_id = intval($request->input('course_id', 0));

        if ($user_id <= 0 || $course_id <= 0) {
            return response()->json(['error' => true, 'message' => 'Invalid user or course ID']);
        }

        try {
            return \DB::transaction(function () use ($user_id, $course_id) {
                // 🧩 Step 0: Check if user already got spin bonus
                $spinResult = \DB::table('wheel_spin_info')
                    ->where('user_id', $user_id)
                    ->select('claimed')
                    ->first();

                $hasSpinBonus = ($spinResult && $spinResult->claimed == 1);

                // 🧩 Step 1: মোট ভিডিও সংখ্যা বের করা
                $total = \DB::table('course_videos')->count();

                if ($total == 0) {
                    throw new \Exception("No videos found for this course.");
                }

                // 🧩 Step 2: ইউজার কতগুলো ভিডিও সম্পূর্ণ করেছে
                $completed = \DB::table('course_progress_videos')
                    ->where('user_id', $user_id)
                    ->where('is_complete', 1)
                    ->count();

                // 🧩 Step 3: শতকরা হিসাব
                $percent = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

                // 🧩 Step 4: কোর্স সম্পূর্ণ কিনা যাচাই করে আপডেট করা
                $status = "incomplete";
                $claimedValue = $hasSpinBonus ? 1 : 0;

                if ($completed >= $total) {
                    // ✅ কোর্স সম্পূর্ণ
                    \DB::table('course_info')->updateOrInsert(
                        ['user_id' => $user_id, 'course_id' => $course_id],
                        ['completed' => 1, 'claimed' => $claimedValue, 'updated_at' => now()]
                    );
                    $status = "completed";
                } else {
                    // ⚠️ অসম্পূর্ণ
                    \DB::table('course_info')->updateOrInsert(
                        ['user_id' => $user_id, 'course_id' => $course_id],
                        ['completed' => 0, 'claimed' => 0, 'updated_at' => now()]
                    );
                    $status = "incomplete";
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Course progress updated successfully',
                    'status' => $status,
                    'completed_videos' => (int)$completed,
                    'total_videos' => (int)$total,
                    'percent' => $percent,
                    'claimed' => $hasSpinBonus ? 1 : 0
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Job Status and Texts
     */
    public function getJobStatus()
    {
        $status = \App\Models\JobStatus::first();
        if (!$status) {
            return response()->json((object)[]);
        }
        
        $data = $status->toArray();
        // Force all status fields to integers for Android compatibility
        foreach ($data as $key => $value) {
            if (in_array($key, ['id', 'updated_at', 'created_at'])) continue;
            $data[$key] = (int)$value;
        }
        
        return response()->json($data);
    }

    public function getJobText(Request $request)
    {
        $type = $request->query('job_type');
        $text = \App\Models\JobText::where('job_type', $type)->first();
        return response()->json($text ?: (object)[]);
    }

    public function getJobTutorial(Request $request)
    {
        $type = $request->query('job_type');
        $tutorial = \App\Models\JobTutorial::where('job_type', $type)->first();
        return response()->json($tutorial ?: (object)[]);
    }

    /**
     * OTP and Password Reset
     */
    public function sendEmailOtp(Request $request)
    {
        $email = $request->input('email');

        if (!$email) {
            return response()->json(['success' => false, 'message' => 'ইমেইল প্রয়োজন']);
        }

        // Check if email exists
        $user = SignUp::where('email', $email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'এই ইমেইলটি রেজিস্ট্রেশনে নেই']);
        }

        $otp = rand(100000, 999999);

        // Insert or Update OTP in otp_verification table
        \DB::table('otp_verification')->updateOrInsert(
            ['email' => $email],
            ['otp' => $otp, 'created_at' => now()]
        );

        try {
            // Temporarily configure mail settings
            \Config::set('mail.mailers.smtp.host', 'rootvabd.com');
            \Config::set('mail.mailers.smtp.port', 465);
            \Config::set('mail.mailers.smtp.encryption', 'ssl');
            \Config::set('mail.mailers.smtp.username', 'otp@rootvabd.com');
            \Config::set('mail.mailers.smtp.password', 'Masud1999@@');
            \Config::set('mail.from.address', 'otp@rootvabd.com');
            \Config::set('mail.from.name', 'Rootva');

            \Mail::html("
                <p>Dear user,</p>
                <p>Your OTP code for password reset is: 
                <strong style='font-size:18px;'>$otp</strong></p>
                <p>This OTP is valid for only 5 minutes.</p>
                <br>
                <p>Regards,<br>Rootva Team</p>
            ", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Your OTP Code for Password Reset');
            });

            return response()->json(['success' => true, 'message' => 'OTP পাঠানো হয়েছে']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'OTP পাঠাতে ব্যর্থ: ' . $e->getMessage()]);
        }
    }

    public function resetPassword(Request $request)
    {
        $email = $request->input('email');
        $otp = $request->input('otp');
        $new_password = $request->input('new_password');

        if (!$email || !$otp || !$new_password) {
            return response()->json(['success' => false, 'message' => 'সব তথ্য পূরণ করুন']);
        }

        // OTP check from database
        $otpRecord = \DB::table('otp_verification')->where('email', $email)->first();

        if (!$otpRecord) {
            return response()->json(['success' => false, 'message' => 'OTP সেট করা হয়নি']);
        }

        $stored_otp = $otpRecord->otp;
        $created_at = strtotime($otpRecord->created_at);
        $current_time = time();
        $validity_duration = 5 * 60; // 5 minutes

        if ($stored_otp != $otp) {
            return response()->json(['success' => false, 'message' => 'OTP ভুল']);
        } elseif (($current_time - $created_at) > $validity_duration) {
            \DB::table('otp_verification')->where('email', $email)->delete();
            return response()->json(['success' => false, 'message' => 'OTP-এর মেয়াদ শেষ হয়েছে']);
        }

        // Update Password
        $user = SignUp::where('email', $email)->first();
        if ($user) {
            $user->password = bcrypt($new_password);
            if ($user->save()) {
                // OTP রেকর্ড ডিলিট করে দাও
                \DB::table('otp_verification')->where('email', $email)->delete();
                return response()->json(['success' => true, 'message' => 'পাসওয়ার্ড রিসেট সফল হয়েছে']);
            }
        }

        return response()->json(['success' => false, 'message' => 'পাসওয়ার্ড আপডেট ব্যর্থ']);
    }

    public function sendWithdrawOtp(Request $request)
    {
        $number = trim($request->input('number', ''));
        $otp = trim($request->input('otp', ''));

        if (empty($number) || empty($otp)) {
            return response()->json(["success" => false, "message" => "Number or OTP missing"]);
        }

        $api_key = "w4yxyJpY112w3wDVDegDTgFgrLpqKGrsejeMz8jN";
        $message = "Your Rootva OTP Code is: $otp";
        $url = "https://api.sms.net.bd/sendsms";

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()->post($url, [
                'api_key' => $api_key,
                'msg' => $message,
                'to' => $number
            ]);

            if ($response->successful()) {
                return response()->json(["success" => true, "message" => "OTP পাঠানো হয়েছে", "otp" => $otp]);
            } else {
                return response()->json(["success" => false, "message" => "SMS পাঠানো ব্যর্থ", "response" => $response->body()]);
            }
        } catch (\Exception $e) {
            return response()->json(["success" => false, "message" => "Error: " . $e->getMessage()]);
        }
    }


    /**
     * Legacy Leadership Level (check_leadership_level2.php)
     */
    public function checkLeadershipLevel(Request $request)
    {
        $user_id = $request->query('user_id');
        if (!$user_id) return response()->json(["status" => "error", "message" => "User ID is required"]);

        $user = SignUp::find($user_id);
        if (!$user) return response()->json(["status" => "error", "message" => "User not found"]);

        $referCode = $user->referCode;

        // Step 1: L1 users
        $level1 = SignUp::where('referredBy', $referCode)->get(['id', 'name', 'referCode', 'referredBy']);
        
        if ($level1->isEmpty()) {
            return response()->json([
                "status" => "success",
                "user_id" => $user_id,
                "reward" => "None",
                "level1_summary" => ["total_referred" => 0, "verified" => 0, "leaders" => 0, "remaining_to_leader" => 15]
            ]);
        }

        $level1_ids = $level1->pluck('id')->toArray();
        $level1_codes = $level1->pluck('referCode')->toArray();

        // Step 2: L2 users
        $level2 = SignUp::whereIn('referredBy', $level1_codes)->get(['id', 'name', 'referCode', 'referredBy']);
        $l2_by_parent_code = $level2->groupBy('referredBy');
        $level2_codes = $level2->pluck('referCode')->toArray();

        // Step 3: L3 users
        $level3 = SignUp::whereIn('referredBy', $level2_codes)->get(['id', 'name', 'referCode', 'referredBy']);
        $l3_by_parent_code = $level3->groupBy('referredBy');

        // Step 4: Bulk verification map
        $all_ids = array_unique(array_merge([$user_id], $level1_ids, $level2->pluck('id')->toArray(), $level3->pluck('id')->toArray()));
        $verified_map = VerificationRequest::whereIn('user_id', $all_ids)
            ->where('status', 'Approved')
            ->select('user_id', \DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        // Step 5: Orders map
        $last_claim = LeadershipClaim::where('user_id', $user_id)->where('leadership_type', 'Silver')->max('claimed_at') ?? '1970-01-01';
        $orders_map = Order::whereIn('user_id', $all_ids)
            ->where('order_status', 'Delivered')
            ->where('created_at', '>', $last_claim)
            ->select('user_id', \DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->pluck('count', 'user_id');

        $orderCount = $orders_map[$user_id] ?? 0;

        // Step 6: Compute
        $verifiedCount = 0;
        $leaderCount = 0;
        $silverCandidates = [];
        $goldCandidates = [];
        $diamondCandidates = [];
        $topCandidates = [];

        foreach ($level1 as $m) {
            $l2_under = $l2_by_parent_code[$m->referCode] ?? collect();
            $verifiedL2 = $l2_under->filter(fn($l2) => ($verified_map[$l2->id] ?? 0) > 0)->count();

            $leaderTimes = intdiv($verifiedL2, 15);
            if ($leaderTimes >= 1) {
                $leaderCount++;
                $cand = [
                    "id" => $m->id,
                    "name" => $m->name,
                    "referCode" => $m->referCode,
                    "verified_referrals" => $verifiedL2,
                    "is_leader" => true,
                    "leader_times" => $leaderTimes
                ];
                $silverCandidates[] = $cand;

                // Sub-leaders check
                $subs = $l2_under;
                $subLeaders = 0;
                foreach ($subs as $sub) {
                    $l3_under = $l3_by_parent_code[$sub->referCode] ?? collect();
                    $vL3 = $l3_under->filter(fn($u3) => ($verified_map[$u3->id] ?? 0) > 0)->count();
                    if ($vL3 >= 15) $subLeaders++;
                }

                if ($subLeaders >= 10) {
                    $goldCandidates[] = $cand;
                    if ($orderCount >= 3) $diamondCandidates[] = $cand;
                    if ($orderCount >= 10) $topCandidates[] = $cand;
                }
            }

            if (($verified_map[$m->id] ?? 0) > 0) $verifiedCount++;
        }

        $leaderTimesOverall = intdiv($verifiedCount, 15);
        $silverTimes = ($orderCount >= 1) ? intdiv($leaderCount, 10) : 0;
        $goldTimes = (count($goldCandidates) >= 10 && $orderCount >= 3) ? intdiv(count($goldCandidates), 10) : 0;
        $diamondTimes = (count($diamondCandidates) >= 10 && $orderCount >= 5) ? intdiv(count($diamondCandidates), 10) : 0;
        $topTimes = (count($topCandidates) >= 10 && $orderCount >= 10) ? intdiv(count($topCandidates), 10) : 0;

        $reward = "None";
        if ($topTimes >= 1) $reward = "Top $topTimes";
        elseif ($diamondTimes >= 1) $reward = "Diamond $diamondTimes";
        elseif ($goldTimes >= 1) $reward = "Gold $goldTimes";
        elseif ($silverTimes >= 1) $reward = "Silver $silverTimes";
        elseif ($leaderTimesOverall >= 1) $reward = "Rootva Leader $leaderTimesOverall";

        // Auto-insert Leader Transactions (Optimized)
        if ($leaderTimesOverall > 0) {
            $alreadyGiven = Transaction::where('user_id', $user_id)->where('description', 'Rootva Leader reward')->count();
            if ($leaderTimesOverall > $alreadyGiven) {
                for ($i = 0; $i < ($leaderTimesOverall - $alreadyGiven); $i++) {
                    Transaction::create([
                        'user_id' => $user_id,
                        'refer_id' => $referCode,
                        'amount' => 80,
                        'type' => 'income',
                        'payment_gateway' => 'Leadership Bonus',
                        'description' => 'Rootva Leader reward',
                        'update_at' => date('d-m-Y h:i A'),
                        'created_at' => date('d-m-Y h:i A'),
                        'date' => now()
                    ]);
                }
            }
        }

        // Handle Silver/Gold/Diamond/Top Requests
        $leaderships = ["Silver" => $silverTimes, "Gold" => $goldTimes, "Diamond" => $diamondTimes, "Top" => $topTimes];
        $amounts = ["Silver" => 500, "Gold" => 1000, "Diamond" => 2000, "Top" => 4000];
        foreach ($leaderships as $type => $times) {
            if ($times <= 0) continue;
            if (LeadershipRewardRequest::where('user_id', $user_id)->where('reward_type', $type)->where('status', 'Pending')->exists()) continue;
            
            $approvedTimes = LeadershipRewardRequest::where('user_id', $user_id)->where('reward_type', $type)->where('status', 'Approved')->sum('times');
            if ($times > $approvedTimes) {
                $newTimes = $times - $approvedTimes;
                LeadershipRewardRequest::create([
                    'user_id' => $user_id,
                    'reward_type' => $type,
                    'times' => $newTimes,
                    'amount' => $amounts[$type] * $newTimes,
                    'status' => 'Pending'
                ]);
            }
        }

        return response()->json([
            "status" => "success",
            "user_id" => $user_id,
            "reward" => $reward,
            "orders" => $orderCount,
            "level1_summary" => [
                "total_referred" => count($level1),
                "verified" => $verifiedCount,
                "leaders" => $leaderCount,
                "remaining_to_leader" => max(0, 15 - $verifiedCount),
                "reward" => ($leaderTimesOverall >= 1) ? "Rootva Leader $leaderTimesOverall" : null
            ],
            "silver_summary" => ["total_candidates" => count($silverCandidates), "times" => $silverTimes, "total_orders" => $orderCount, "reward" => ($silverTimes >= 1) ? "Silver $silverTimes" : null],
            "gold_summary" => ["total_candidates" => count($goldCandidates), "times" => $goldTimes, "total_orders" => $orderCount, "reward" => ($goldTimes >= 1) ? "Gold $goldTimes" : null],
            "diamond_summary" => ["total_candidates" => count($diamondCandidates), "times" => $diamondTimes, "total_orders" => $orderCount, "reward" => ($diamondTimes >= 1) ? "Diamond $diamondTimes" : null],
            "top_summary" => ["total_candidates" => count($topCandidates), "times" => $topTimes, "total_orders" => $orderCount, "reward" => ($topTimes >= 1) ? "Top $topTimes" : null],
            "silver_candidates" => $silverCandidates,
            "gold_candidates" => $goldCandidates,
            "diamond_candidates" => $diamondCandidates,
            "top_candidates" => $topCandidates,
            "user_level_rewards" => [
                "Rootva Leader" => $leaderTimesOverall,
                "Silver" => $silverTimes,
                "Gold" => $goldTimes,
                "Diamond" => $diamondTimes,
                "Top" => $topTimes
            ]
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
            'update_at' => now()->format('Y-m-d H:i:s')
        ]);

        return response()->json([
            "status" => "success",
            "message" => "✅ Spin Target Bonus added!",
            "amount" => $bonus_amount,
            "level1_verified" => $level1_verified,
            "level2_verified" => $level2_verified
        ]);
    }

    /**
     * Legacy Payment SMS Hook (payment_sms_hook.php)
     */
    public function handlePaymentSmsHook(Request $request)
    {
        $secret = 'nOiNnz6Pl72tUFwxVeifvhCD0YsGzKhZRPZ9YYzxwfI=';
        $signature = $request->header('X-Signature');
        $deviceId = $request->header('X-Device-Id');
        
        $body = $request->getContent();
        $calcSig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        if (!$deviceId || $calcSig !== $signature) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $gateway = strtolower(trim($payload['gateway'] ?? ''));
        $txnId = trim($payload['transaction_id'] ?? '');
        $amount = (float)($payload['amount'] ?? 0);

        if (!$gateway || !$txnId || $amount <= 0) {
            return response()->json(['success' => false, 'message' => 'Missing fields'], 422);
        }

        IncomingPaymentSms::updateOrCreate(
            ['transaction_id' => $txnId, 'gateway' => $gateway],
            [
                'sender' => $payload['sender'] ?? '',
                'account_number' => $payload['account_number'] ?? null,
                'amount' => $amount,
                'received_at' => $payload['received_at'] ?? now(),
                'raw_text' => $payload['raw_text'] ?? '',
                'device_id' => $deviceId
            ]
        );

        $match = VerificationRequest::where('status', 'Pending')
            ->where('transaction_id', $txnId)
            ->where('amount', $amount)
            ->first();

        if ($match && $amount >= 250) {
            DB::transaction(function () use ($match, $txnId, $gateway) {
                $user = SignUp::find($match->user_id);
                $time = date("d-m-Y h:i A");
                $raw_time = now()->toDateTimeString();

                $user->update(['is_verified' => 1, 'verified_at' => $time, 'verified_raw_time' => $raw_time]);
                \App\Models\Notification::create(['user_id' => $user->id, 'message' => "আপনার ভেরিফিকেশন সফল হয়েছে", 'created_at' => $time]);
                $match->update(['status' => 'Approved', 'updated_at' => $time, 'verified_raw_time' => $raw_time]);
                IncomingPaymentSms::where('transaction_id', $txnId)->where('gateway', $gateway)->update(['processed' => 'Matched', 'matched_request_id' => $match->id]);
            });
            return response()->json(['success' => true, 'message' => 'Matched & Approved']);
        }

        return response()->json(['success' => true, 'message' => 'Logged']);
    }

    /**
     * Legacy Recharge (recharge.php)
     */
    public function recharge(Request $request)
    {
        if ($request->input('secret_key') !== 'Masud@1234567890') {
            return response()->json(["status" => false, "message" => "Unauthorized"], 401);
        }

        $user_id = $request->input('user_id');
        $amount = $request->input('amount');
        $user = SignUp::find($user_id);

        if (!$user || $user->voucher_balance < $amount) {
            return response()->json(["status" => false, "message" => "Insufficient balance"]);
        }

        $tran_id = uniqid("TRX_");
        DB::transaction(function () use ($user, $amount, $tran_id, $request) {
            $user->decrement('voucher_balance', $amount);
            RechargeTransaction::create([
                'user_id' => $user->id,
                'number' => $request->input('number'),
                'operator' => $request->input('operator'),
                'package_id' => $request->input('package_id'),
                'package_title' => $request->input('title'),
                'amount' => $amount,
                'tran_id' => $tran_id,
                'status' => 'pending'
            ]);
        });

        // SohojPay API Call
        $response = Http::withHeaders(['SOHOJPAY-API-KEY' => 'F3Jj0G6ipwXrlZg985y7wiN0yUxQ8IFiCQSw1kdwmZy6IniniHf5MqoiBozf'])
            ->post('https://secure.sohojpaybd.com/recharge/request/create', [
                "number" => $request->input('number'),
                "type" => 1,
                "operator" => $request->input('operator'),
                "package_id" => $request->input('package_id'),
                "tran_id" => $tran_id,
                "amount" => $amount
            ]);

        $status = $response->successful() && $response->json('status') ? 'success' : 'failed';
        if ($status === 'failed') {
            $user->increment('voucher_balance', $amount);
        }

        RechargeTransaction::where('tran_id', $tran_id)->update(['status' => $status, 'api_response' => $response->body()]);

        return response()->json(["status" => ($status === 'success'), "message" => ($status === 'success' ? "Recharge successful" : "Recharge failed"), "tran_id" => $tran_id]);
    }

    public function getSimOfferManage()
    {
        $settings = \App\Models\SimOfferManage::first();
        
        return response()->json([
            'success'       => true,
            'status_on_off' => $settings ? (int)$settings->status : 1,
            'notice_text'   => $settings ? $settings->notice_text : 'No notice available'
        ]);
    }

    public function getUserOfferHistory(Request $request)
    {
        $user_id = $request->query('user_id');
        
        if (empty($user_id)) {
            return response()->json(["success" => false, "message" => "User ID missing"]);
        }

        $history = \App\Models\SimOfferRequest::where('user_id', $user_id)
            ->with('offer')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function($req) {
                return [
                    'request_id'    => $req->id,
                    'phone_number'  => $req->phone_number,
                    'price'         => (double) $req->price,
                    'status'        => $req->status,
                    'reject_reason' => $req->reject_reason,
                    'created_at'    => $req->created_at instanceof \Carbon\Carbon ? $req->created_at->toDateTimeString() : $req->created_at,
                    'title'         => $req->offer->title ?? 'N/A',
                    'offer_details' => $req->offer->offer_details ?? 'N/A',
                    'operator_name' => $req->offer->operator_name ?? 'N/A',
                    'offer_price'   => $req->offer->offer_price ?? 0,
                ];
            });
        
        return response()->json($history);
    }

    public function submitSimOfferRequest(Request $request)
    {
        $userId = $request->input('user_id');
        $offerId = $request->input('offer_id');
        $phone = $request->input('phone_number');
        $price = (double)$request->input('price');

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'ইউজার পাওয়া যায়নি']);
        }

        if ($user->voucher_balance < $price) {
            return response()->json(['success' => false, 'message' => 'পর্যাপ্ত ব্যালেন্স নেই']);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // ব্যালেন্স কমানো
            $user->update([
                'voucher_balance' => $user->voucher_balance - $price
            ]);

            // রিকোয়েস্ট সেভ করা
            $req = \App\Models\SimOfferRequest::create([
                'user_id' => $userId,
                'offer_id' => $offerId,
                'phone_number' => $phone,
                'price' => $price,
                'status' => 'pending'
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['success' => true, 'message' => 'রিকোয়েস্টটি সফলভাবে পাঠানো হয়েছে']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'রিকোয়েস্ট পাঠাতে ব্যর্থ হয়েছে']);
        }
    }

    /**
     * Confirm SIM Offer (confirm_sim_offer.php)
     */
    public function confirmSimOffer(Request $request)
    {
        $id = $request->input('request_id');
        $simRequest = \App\Models\SimOfferRequest::find($id);
        
        if ($simRequest) {
            $simRequest->status = 'confirmed';
            if ($simRequest->save()) {
                return response()->json([
                    "error" => false,
                    "message" => "Offer confirmed"
                ]);
            }
        }
        
        return response()->json([
            "error" => true,
            "message" => "Failed to update"
        ]);
    }


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
        $totalOrders = \DB::table('orders')
            ->where('user_id', $userId)
            ->where('order_status', 'Delivered')
            ->where('created_at', '>', $startDate)
            ->count();

        // 🟢 Step 4: Eligibility Check
        $level1VerifiedCount = count($level1VerifiedIds);
        $isEligible = ($level1VerifiedCount >= 30 && $level1Active >= 10 && $level2VerifiedTotal >= 60 && $totalOrders >= 1);

        // 🟢 Step 5: Status
        $lastRequest = \App\Models\SalaryRequest::where('user_id', $userId)
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

    public function applySalaryRequest(Request $request)
    {
        $userId = $request->input('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID missing']);
        }

        // Check if already applied and pending
        $existing = \App\Models\SalaryRequest::where('user_id', $userId)
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
        $salaryRequest = \App\Models\SalaryRequest::create([
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

    public function getSalaryRequestStatus(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['error' => true, 'message' => 'User ID missing']);
        }

        $latestRequest = \App\Models\SalaryRequest::where('user_id', $userId)
            ->where('request_type', 'monthly_salary')
            ->latest('requested_at')
            ->first();

        return response()->json([
            'status' => $latestRequest ? $latestRequest->status : 'None'
        ]);
    }

    public function getSpinProgress(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['error' => true, 'message' => 'User ID missing']);
        }

        $user = \DB::table('sign_up')->where('id', $userId)->first(['referCode']);
        if (!$user) {
            return response()->json(['error' => true, 'message' => 'User not found']);
        }

        $referCode = $user->referCode;

        // Step 1: Get Level 1 referrals
        $level1 = \DB::table('sign_up')
            ->where('referredBy', $referCode)
            ->get(['id', 'referCode']);

        $level1Ids = $level1->pluck('id')->toArray();
        $level1ReferCodes = $level1->pluck('referCode')->filter()->toArray();

        // Step 2: Count verified Level 1
        $level1_verified = 0;
        if (!empty($level1Ids)) {
            $level1_verified = \DB::table('verification_requests')
                ->where('status', 'Approved')
                ->whereIn('user_id', $level1Ids)
                ->count();
        }

        // Step 3: Get Level 2 referrals
        $level2Ids = [];
        if (!empty($level1ReferCodes)) {
            $level2Ids = \DB::table('sign_up')
                ->whereIn('referredBy', $level1ReferCodes)
                ->pluck('id')
                ->toArray();
        }

        // Step 4: Count verified Level 2
        $level2_verified = 0;
        if (!empty($level2Ids)) {
            $level2_verified = \DB::table('verification_requests')
                ->where('status', 'Approved')
                ->whereIn('user_id', $level2Ids)
                ->count();
        }

        // Step 5: Check if bonus already given
        $already_given = \DB::table('bonus_tracker')
            ->where('user_id', $userId)
            ->where('bonus_type', 'spin_target')
            ->exists();

        $eligible = ($level1_verified >= 10 && $level2_verified >= 10 && !$already_given);

        return response()->json([
            'level1_verified' => (int)$level1_verified,
            'level2_verified' => (int)$level2_verified,
            'eligible' => (bool)$eligible
        ]);
    }

    public function getMathIncome(Request $request)
    {
        $user_id = $request->input('user_id');

        if (!$user_id) {
            return response()->json(['error' => 'User ID is required']);
        }

        $total_amount = \App\Models\Transaction::where('user_id', $user_id)
            ->where('payment_gateway', 'Typing Job')
            ->sum('amount');

        return response()->json([
            'math_income' => (float)$total_amount
        ]);
    }

    public function convertToVoucher(Request $request)
    {
        $userId = $request->input('user_id');
        $amount = (double) $request->input('amount');

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'ইউজার পাওয়া যায়নি']);
        }

        $walletBalance = (double) $user->wallet_balance;
        $voucherBalance = (double) $user->voucher_balance;

        if ($amount <= 0 || $walletBalance < $amount) {
            return response()->json(['success' => false, 'message' => 'পর্যাপ্ত ব্যালেন্স নেই']);
        }

        // 2% charge
        $charge = $amount * 0.02;
        $finalAmount = $amount - $charge;

        $newWallet = $walletBalance - $amount;
        $newVoucher = $voucherBalance + $finalAmount;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $user->update([
                'wallet_balance' => $newWallet,
                'voucher_balance' => $newVoucher
            ]);

            $currentTime = date("d-m-Y h:i A");
            $now = date("Y-m-d H:i:s");

            // 1. Transaction for Wallet deduction
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $amount,
                'type' => 'payment',
                'payment_gateway' => 'Wallet',
                'description' => 'Voucher Balance Convert',
                'update_at' => $currentTime,
                'created_at' => $currentTime,
                'date' => $now
            ]);

            // 2. Transaction for Voucher addition
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $finalAmount,
                'type' => 'voucher_convert',
                'payment_gateway' => 'Voucher',
                'description' => 'Voucher Balance Added (After 2% charge)',
                'update_at' => $currentTime,
                'created_at' => $currentTime,
                'date' => $now
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'converted_amount' => $finalAmount,
                'charge' => $charge,
                'wallet_balance' => $newWallet,
                'voucher_balance' => $newVoucher
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'ব্যালেন্স আপডেট ব্যর্থ হয়েছে']);
        }
    }

    public function getVoucherBalance(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);

        if ($user) {
            return response()->json([
                'wallet_balance' => $user->wallet_balance,
                'voucher_balance' => $user->voucher_balance
            ]);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    /**
     * Review Job Endpoints
     */

    public function getAvailableReviewJobs(Request $request)
    {
        $userId = $request->input('user_id');
        
        // Fetch jobs that are not locked by others, or locked by this user, and have remaining target
        $jobs = \App\Models\ReviewJob::where('remaining_target', '>', 0)
            ->where(function($query) use ($userId) {
                $query->whereNull('locked_by')
                      ->orWhere('locked_by', 0)
                      ->orWhere('locked_by', $userId);
            })
            ->get();
            
        return response()->json($jobs);
    }

    public function getLockReviewJobs(Request $request)
    {
        $userId = $request->input('user_id');
        $jobs = \App\Models\ReviewJob::where('locked_by', $userId)->get();
        return response()->json($jobs);
    }

    public function getReviewJobSocial()
    {
        $socials = \Illuminate\Support\Facades\DB::table('review_job_socials')->orderBy('id', 'desc')->first();
        
        if ($socials) {
            return response()->json([
                'status' => true,
                'message' => 'লিংক সফলভাবে পাওয়া গেছে',
                'review_job_socials' => $socials
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'লিংক পাওয়া যায়নি'
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function lockReviewJob(Request $request)
    {
        $userId = $request->input('user_id');
        $jobId = $request->input('job_id');
        
        $job = \App\Models\ReviewJob::find($jobId);
        if ($job) {
            if ($job->locked_by && $job->locked_by != $userId) {
                return response()->json(['success' => false, 'message' => 'Job already locked by another user']);
            }
            
            $job->update([
                'locked_by' => $userId,
                'scheduled_at' => now()
            ]);
            return response()->json(['success' => true, 'message' => 'Job locked successfully']);
        }
        return response()->json(['success' => false, 'message' => 'Job not found']);
    }

    public function unlockReviewJob(Request $request)
    {
        $jobId = $request->input('job_id');
        $job = \App\Models\ReviewJob::find($jobId);
        if ($job) {
            $job->update([
                'locked_by' => null,
                'scheduled_at' => null
            ]);
            return response()->json(['success' => true, 'message' => 'Job unlocked']);
        }
        return response()->json(['success' => false, 'message' => 'Job not found']);
    }

    public function submitReviewJobProof(Request $request)
    {
        $user_id = $request->input('user_id');
        $refer_id = $request->input('refer_id');
        $job_id = $request->input('job_id');
        $message = $request->input('message');
        $number = $request->input('number');

        if (!$user_id || !$refer_id || !$job_id || empty($message) || empty($number)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Missing required fields!'
            ]);
        }

        $image_url = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = uniqid("proof_", true) . "." . $file->getClientOriginalExtension();
            $file->move(public_path('reviewJobImage'), $fileName);
            $image_url = 'reviewJobImage/' . $fileName;
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'No image uploaded or upload error!'
            ]);
        }

        try {
            \App\Models\ReviewSubmission::create([
                'job_id' => $job_id,
                'refer_id' => $refer_id,
                'worker_user_id' => $user_id,
                'proof_image_url' => $image_url,
                'proof_message' => $message,
                'number' => $number,
                'status' => 'pending'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Proof uploaded and saved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Legacy Notifications (get_notifications.php)
     */
    public function getNotifications(Request $request)
    {
        $userId = $request->query('user_id');
        $notifications = \App\Models\Notification::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * Legacy Mark Notifications Read (mark_notifications_read.php)
     */
    public function markNotificationsAsRead(Request $request)
    {
        $userId = $request->input('user_id');
        \App\Models\Notification::where('user_id', $userId)->update(['is_read' => 1]);
        
        return response()->json(['success' => true]);
    }
    /**
     * Legacy Add Microjob (add_microjob.php)
     */
    public function addMicrojob(Request $request)
    {
        $user_id = $request->input('user_id');
        $title = $request->input('title');
        $description = $request->input('description');
        $amount_per_worker = $request->input('amount_per_worker');
        $total_target = $request->input('total_target');
        $job_url = $request->input('job_url');
        $total_amount = $request->input('total_amount');

        $current_time = now()->format('Y-m-d H:i:s');
        $current_time2 = now()->format('d-m-Y H:i A');

        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }

        if ($user->voucher_balance < $total_amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance']);
        }

        $image_name = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $image_name = time() . "_" . $file->getClientOriginalName();
            $file->move(public_path('service/microjobs/microjobImage'), $image_name);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Create Microjob
            $job = Microjob::create([
                'user_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'amount_per_worker' => $amount_per_worker,
                'total_target' => $total_target,
                'total_amount' => $total_amount,
                'image_url' => $image_name,
                'job_url' => $job_url,
                'remaining_target' => $total_target,
                'status' => 'Pending',
                'created_at' => $current_time
            ]);

            // Insert into transactions
            $transaction = Transaction::create([
                'user_id' => $user_id,
                'amount' => $total_amount,
                'payment_gateway' => 'Microjob Post',
                'type' => 'voucher_payment',
                'description' => 'Payment For Job Post',
                'update_at' => $current_time2,
                'created_at' => $current_time,
                'date' => $current_time
            ]);

            // Deduct Balance
            $user->decrement('voucher_balance', $total_amount);

            // Update Microjob with transaction_id
            $job->update(['transaction_id' => $transaction->id]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Microjob created'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Legacy Posted Jobs (get_posted_jobs.php)
     */
    public function getPostedJobs(Request $request)
    {
        $user_id = $request->query('user_id');
        
        if (!$user_id) {
            return response()->json([]);
        }

        $jobs = Microjob::where('user_id', $user_id)
            ->where('status', 'approved')
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($jobs);
    }

    /**
     * Legacy Job Submissions (get_job_submissions.php)
     */
    public function getJobSubmissions(Request $request)
    {
        $job_id = $request->query('job_id');
        
        if (!$job_id) {
            return response()->json([]);
        }

        $submissions = \Illuminate\Support\Facades\DB::table('microjob_submissions as s')
            ->join('sign_up as u', 's.worker_user_id', '=', 'u.id')
            ->where('s.job_id', $job_id)
            ->select('s.*', 'u.name', 'u.number')
            ->orderBy('s.created_at', 'desc')
            ->get();
            
        return response()->json($submissions);
    }

    /**
     * Legacy Update Submission Status (update_submission_status.php)
     */
    public function updateSubmissionStatus(Request $request)
    {
        $submission_id = $request->input('submission_id');
        $status = $request->input('status'); // approved or rejected
        $reject_reason = $request->input('reject_reason');

        if (!$submission_id || !$status) {
            return response()->json(['status' => 'error', 'message' => 'Missing parameters']);
        }

        $now = now()->toDateTimeString();
        $current_time = now()->format('d-m-Y h:i A');

        $sub = \Illuminate\Support\Facades\DB::table('microjob_submissions')->where('id', $submission_id)->first();
        if (!$sub) {
            return response()->json(['status' => 'error', 'message' => 'Invalid submission']);
        }

        $job = \Illuminate\Support\Facades\DB::table('microjobs')->where('id', $sub->job_id)->first();
        if (!$job) {
            return response()->json(['status' => 'error', 'message' => 'Invalid job']);
        }

        $amount = (double) $job->amount_per_worker;
        $worker_user_id = $sub->worker_user_id;

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($status == 'approved') {
                // Update submission
                \Illuminate\Support\Facades\DB::table('microjob_submissions')
                    ->where('id', $submission_id)
                    ->update(['status' => 'approved', 'reject_reason' => null]);

                // Update wallet_balance
                $user = SignUp::find($worker_user_id);
                if ($user) {
                    $user->increment('wallet_balance', $amount);

                    // Insert transaction
                    Transaction::create([
                        'user_id' => $worker_user_id,
                        'amount' => $amount,
                        'type' => 'income',
                        'payment_gateway' => 'Microjob',
                        'description' => "Earned from microjob ID: " . $sub->job_id,
                        'update_at' => $current_time,
                        'created_at' => $now,
                        'date' => $now
                    ]);
                }
            } else if ($status == 'rejected') {
                // Increase job target
                \Illuminate\Support\Facades\DB::table('microjobs')
                    ->where('id', $sub->job_id)
                    ->increment('remaining_target');

                // Update submission
                \Illuminate\Support\Facades\DB::table('microjob_submissions')
                    ->where('id', $submission_id)
                    ->update(['status' => 'rejected', 'reject_reason' => $reject_reason]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Legacy Update Microjob Status (update_microjob_status.php)
     */
    public function updateMicrojobStatus(Request $request)
    {
        $job_id = $request->input('job_id');
        $status = $request->input('status'); // 0 or 1

        if ($job_id !== null && $status !== null) {
            \Illuminate\Support\Facades\DB::table('microjobs')
                ->where('id', $job_id)
                ->update(['is_active' => $status]);

            return response()->json([
                'success' => true,
                'message' => 'Job status updated successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid parameters'
        ]);
    }

    /**
     * Legacy User Microjob Posts (get_users_microjobs_posts.php)
     */
    public function getUserMicrojobsPosts(Request $request)
    {
        $user_id = $request->query('user_id');
        
        if (!$user_id) {
            return response()->json(['error' => true, 'message' => 'Invalid user ID']);
        }

        $jobs = Microjob::where('user_id', $user_id)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json([
            'error' => false,
            'microjobs' => $jobs
        ]);
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
                    'update_at' => $currentTime
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
     * Legacy Verify Password (verify_password.php)
     */
    public function verifyPassword(Request $request)
    {
        $userId = $request->input('user_id');
        $password = $request->input('password');

        if (!$userId || !$password) {
            return response()->json(["status" => false, "message" => "Invalid request"]);
        }

        $user = SignUp::find($userId);

        if (!$user) {
            return response()->json(["status" => false, "message" => "User not found"]);
        }

        // Password যাচাই (Plain text এবং Hash উভয়ই সাপোর্ট করবে আপনার পুরনো কোডের মতো)
        if ($password === $user->password || Hash::check($password, $user->password)) {
            return response()->json(["status" => true, "message" => "Password verified"]);
        } else {
            return response()->json(["status" => false, "message" => "Wrong password"]);
        }
    }

    /**
     * Legacy Winners by Date (get_daily_winners_by_date.php)
     */
    public function getWinnersByDate(Request $request)
    {
        $date = $request->query('date');
        
        if (!$date) {
            return response()->json(['status' => false, 'message' => 'Date is required', 'winner' => []]);
        }

        $start_time = $date . ' 00:00:00';
        $end_time = $date . ' 23:59:59';

        $winners = \Illuminate\Support\Facades\DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start_time, $end_time])
            ->select(
                's.referredBy as refer_id',
                'r.id as user_id',
                'r.name',
                'r.profile_pic_url',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_verifications')
            )
            ->groupBy('s.referredBy', 'r.id', 'r.name', 'r.profile_pic_url')
            ->having('total_verifications', '>=', 4)
            ->orderBy('total_verifications', 'desc')
            ->limit(1)
            ->get();

        return response()->json([
            'status' => true,
            'winner' => $winners
        ]);
    }

    /**
     * Legacy Today Live Ranking (get_daily_live_ranking.php)
     */
    public function getTodayLiveRanking()
    {
        $date = now()->format('Y-m-d');
        $start_time = $date . ' 00:00:00';
        $end_time = $date . ' 23:59:59';

        $ranking = \Illuminate\Support\Facades\DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start_time, $end_time])
            ->select(
                's.referredBy as referrer_id',
                'r.name',
                'r.profile_pic_url',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_verifications')
            )
            ->groupBy('s.referredBy', 'r.name', 'r.profile_pic_url')
            ->orderBy('total_verifications', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'ranking' => $ranking
        ]);
    }

    /**
     * Legacy Weekly Winner (get_weekly_winner.php)
     */
    public function getWeeklyWinner()
    {
        // Weekly time range calculation
        // Saturday 06:00 AM to Friday 11:59 PM
        $startOfWeek = date('Y-m-d 06:00:00', strtotime('last Saturday'));
        $endOfWeek = date('Y-m-d 23:59:59', strtotime('this Friday'));

        $sql = "
        WITH RECURSIVE referral_tree AS (
            SELECT s.id AS user_id, s.referredBy AS parent_id, 1 AS level
            FROM sign_up s
            WHERE s.referredBy IS NOT NULL

            UNION ALL

            SELECT s2.id, s2.referredBy, rt.level + 1
            FROM sign_up s2
            JOIN referral_tree rt ON s2.referredBy = rt.user_id
        )

        SELECT
            rt.parent_id AS user_id,
            s.name,
            s.profile_pic_url,
            COUNT(v.id) AS total_verifications
        FROM referral_tree rt
        JOIN sign_up s ON rt.parent_id = s.id
        JOIN verification_requests v ON v.user_id = rt.user_id
        WHERE rt.level = 1
          AND v.status = 'Approved'
          AND v.verified_raw_time BETWEEN ? AND ?
        GROUP BY rt.parent_id, s.name, s.profile_pic_url
        HAVING total_verifications >= 20
        ORDER BY total_verifications DESC
        LIMIT 1
        ";

        $winners = \Illuminate\Support\Facades\DB::select($sql, [$startOfWeek, $endOfWeek]);

        return response()->json([
            'status' => true,
            'start_of_week' => $startOfWeek,
            'end_of_week' => $endOfWeek,
            'ranking' => $winners
        ]);
    }

    /**
     * Legacy Weekly Ranking (get_weekly_ranking.php)
     */
    public function getWeeklyRanking()
    {
        $today = now()->format('Y-m-d');
        $dayOfWeek = now()->dayOfWeek; // 0 = Sunday, 6 = Saturday in Carbon

        if ($dayOfWeek == 6) {
            $startOfWeek = $today;
        } else {
            $startOfWeek = date("Y-m-d", strtotime("last saturday"));
        }

        $endOfWeek = date("Y-m-d", strtotime($startOfWeek . " +6 days"));

        $startTime = $startOfWeek . " 00:00:00";
        $endTime = $endOfWeek . " 23:59:59";

        $ranking = \Illuminate\Support\Facades\DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$startTime, $endTime])
            ->select(
                's.referredBy as referrer_id',
                'r.name',
                'r.profile_pic_url',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_verifications')
            )
            ->groupBy('s.referredBy', 'r.name', 'r.profile_pic_url')
            ->orderBy('total_verifications', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'start_of_week' => $startOfWeek,
            'end_of_week' => $endOfWeek,
            'ranking' => $ranking
        ]);
    }

    /**
     * Legacy Weekly Winners by Date (get_weekly_winners_by_date.php)
     */
    public function getWeeklyWinnersByDate(Request $request)
    {
        $week_start_date = $request->query('week_start_date');
        
        if (!$week_start_date) {
            return response()->json(['status' => false, 'message' => 'Week start date is required', 'winner' => []]);
        }

        $week_end_date = date('Y-m-d', strtotime($week_start_date . ' +6 days'));
        $start_time = $week_start_date . ' 00:00:00';
        $end_time = $week_end_date . ' 23:59:59';

        $winners = \Illuminate\Support\Facades\DB::table('verification_requests as vr')
            ->join('sign_up as s', 'vr.user_id', '=', 's.id')
            ->join('sign_up as r', 's.referredBy', '=', 'r.referCode')
            ->where('vr.status', 'Approved')
            ->whereBetween('vr.verified_raw_time', [$start_time, $end_time])
            ->select(
                's.referredBy as refer_id',
                'r.id as user_id',
                'r.name',
                'r.profile_pic_url',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_verifications')
            )
            ->groupBy('s.referredBy', 'r.id', 'r.name', 'r.profile_pic_url')
            ->having('total_verifications', '>=', 20)
            ->orderBy('total_verifications', 'desc')
            ->limit(1)
            ->get();

        return response()->json([
            'status' => true,
            'winner' => $winners,
            'week_info' => [
                'start_date' => $week_start_date,
                'end_date' => $week_end_date
            ]
        ]);
    }
}

