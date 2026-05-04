<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MoneyRequest;
use App\Models\PaymentNumber;

class LegacyDepositController extends Controller
{
    /**
     * Legacy Money Requests (get_money_requests.php)
     */
    public function getMoneyRequests(Request $request)
    {
        $user_id = $request->query('user_id') ?? $request->input('user_id');
        
        if ($user_id) {
            $requests = MoneyRequest::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get();
                
            return response()->json($requests);
        }
        
        return response()->json([]);
    }

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
                'success' => false,
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
            MoneyRequest::create([
                'user_id' => $user_id,
                'title' => $name,
                'account_number' => $account_number,
                'amount' => $amount,
                'transaction_id' => $transaction_id,
                'payment_gateway' => $payment_gateway,
                'created_at' => $current_time
            ]);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => "রিকোয়েস্ট সফলভাবে সাবমিট হয়েছে"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => "রিকোয়েস্ট সাবমিট করতে ব্যর্থ"
            ]);
        }
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
}
