<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\WithdrawRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LegacyWithdrawController extends Controller
{
    /**
     * Legacy Withdraw Requests (get_withdraw_request.php)
     */
    public function getWithdrawRequests(Request $request)
    {
        $user_id = $request->query('user_id') ?? $request->input('user_id');
        
        if ($user_id) {
            $requests = WithdrawRequest::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get();
                
            return response()->json($requests);
        }
        
        return response()->json([]);
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
            DB::beginTransaction();

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
            WithdrawRequest::create([
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

            Transaction::create([
                'user_id' => $user_id,
                'amount' => $amount,
                'payment_gateway' => $payment_gateway,
                'type' => $transaction_type,
                'description' => $description,
                'update_at' => $current_time,
                'created_at' => $current_time,
                'date' => $current_time
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => ucfirst($balance_type) . " থেকে রিকোয়েস্ট সফলভাবে সাবমিট হয়েছে",
                'fee' => $fee,
                'net_amount' => $net_amount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'status' => 'error', 'message' => "রিকোয়েস্ট সাবমিট করতে ব্যর্থ"]);
        }
    }

    /**
     * Legacy Send Withdraw OTP (send_withdraw_otp.php)
     */
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
            $response = Http::asForm()->post($url, [
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
}
