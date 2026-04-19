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
                'success' => true,
                'message' => 'ডেটা পাওয়া গেছে',
                'users' => $user
            ]);
        }
        return response()->json(['success' => false, 'message' => 'ইউজার খুঁজে পাওয়া যায়নি']);
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
        return response()->json($update);
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
        $referCode = $request->input('referCode');
        $users = SignUp::where('referredBy', $referCode)->get();

        return response()->json([
            'success' => true,
            'referrals' => $users
        ]);
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

        return response()->json(['success' => true, 'message' => 'Request Submitted Successfully']);
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

        return response()->json(['success' => true, 'message' => 'Withdrawal Request Submitted']);
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

        return response()->json(['success' => true, 'message' => 'Verification Request Submitted']);
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

        return response()->json(['success' => true, 'message' => 'Recharge Request Submitted']);
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
        
        return response()->json(['success' => true, 'message' => 'Token Saved']);
    }
}
