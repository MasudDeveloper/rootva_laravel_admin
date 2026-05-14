<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RechargeTransaction;
use App\Models\Transaction;
use App\Models\SignUp;
use App\Traits\LegacyFCMTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LegacyRechargeController extends Controller
{
    use LegacyFCMTrait;

    /**
     * Legacy Recharge (recharge_request.php)
     */
    public function doRecharge(Request $request)
    {
        $user_id = intval($request->input('user_id'));
        $number = trim($request->input('number', ''));
        $operator = trim($request->input('operator', ''));
        $amount = floatval($request->input('amount', 0));
        
        $api_url = "https://www.raseltel.com/api/recharge.php";
        $api_key = "b2b05206f2d526518de81a0f6feb8064";
        $tran_id = "TXN_" . time() . rand(10, 99);
        $now = now()->toDateTimeString();
        $currentTime = now()->format("d-m-Y h:i A");

        // ✅ Validation checks
        if (!$user_id || empty($number) || empty($operator) || $amount <= 0) {
            return response()->json(["status" => false, "message" => "সবগুলো তথ্য প্রদান করুন"]);
        }

        if (strlen($number) != 11) {
            return response()->json(["status" => false, "message" => "মোবাইল নম্বরটি ১১ ডিজিটের হতে হবে"]);
        }

        if ($amount < 10) {
            return response()->json(["status" => false, "message" => "সর্বনিম্ন রিচার্জ ১০ টাকা"]);
        }

        // ✅ Check Balance Before API Call
        $user = DB::table('sign_up')->where('id', $user_id)->first();
        if (!$user || $user->voucher_balance < $amount) {
            return response()->json(["status" => false, "message" => "পর্যাপ্ত ভাউচার ব্যালেন্স নেই"]);
        }

        // Map operator to RaselTel short codes (lowercase) as per documentation
        $operator_code = 'gp';
        $op_upper = strtoupper($operator);
        if (str_contains($op_upper, 'GP')) $operator_code = 'gp';
        elseif (str_contains($op_upper, 'ROBI')) $operator_code = 'robi';
        elseif (str_contains($op_upper, 'AIRTEL')) $operator_code = 'at';
        elseif (str_contains($op_upper, 'BANGLALINK')) $operator_code = 'bl';
        elseif (str_contains($op_upper, 'TELETALK')) $operator_code = 'tt';
        elseif (str_contains($op_upper, 'SKITTO')) $operator_code = 'sk';
        else $operator_code = strtolower($operator);

        // ✅ Step 1: Insert Initial Transaction (Pending)
        DB::table('recharge_transactions')->insert([
            'user_id' => $user_id,
            'number' => $number,
            'amount' => $amount,
            'operator' => $operator,
            'tran_id' => $tran_id,
            'status' => 'pending',
            'created_at' => $now
        ]);

        try {
            $params = [
                "api_key" => $api_key,
                "number" => $number,
                "amount" => $amount,
                "operator" => $operator_code
            ];

            \Log::info("Recharge Request: " . $api_url . "?" . http_build_query($params));

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0'
            ])->timeout(120)->get($api_url, $params);

            \Log::info("Recharge Response: " . $response->body());
            
            $api_data = $response->json();
            $body = $response->body();

            // ✅ Handle success/failure
            $status = 'pending';
            $response_msg = "Recharge Request Sent";

            // If JSON parsing fails but body contains success indicators
            if (!$api_data) {
                if (stripos($body, 'success') !== false || stripos($body, 'API') !== false) {
                    $api_data = ['status' => 'success', 'message' => 'Processed (Text Response)'];
                } else {
                    return response()->json([
                        "status" => false, 
                        "message" => "Invalid API Response", 
                        "response" => $body
                    ]);
                }
            }

            if (isset($api_data['status'])) {
                $api_status = strtolower($api_data['status']);
                if ($api_status === "success") {
                    $status = 'success';
                    $response_msg = "Recharge Successful";
                    $msg = "আপনার {$number} নম্বরে ৳{$amount} রিচার্জ সফল হয়েছে (Txn: {$tran_id})";

                    // Wallet deduct
                    DB::table('sign_up')->where('id', $user_id)->decrement('voucher_balance', $amount);
                    
                    // Log transaction
                    DB::table('transactions')->insert([
                        'user_id' => $user_id,
                        'amount' => $amount,
                        'type' => 'voucher_payment',
                        'payment_gateway' => 'Recharge',
                        'description' => "Mobile Recharge to {$number} ({$operator})",
                        'update_at' => $current_time,
                        'created_at' => $now,
                        'date' => $now
                    ]);

                    $this->giveRechargeCommission($user_id, $amount, $tran_id);

                    // Add notification
                    DB::table('notifications')->insert([
                        'user_id' => $user_id,
                        'message' => $msg,
                        'is_read' => 0,
                        'created_at' => $now
                    ]);
                } elseif ($api_status === "error" || $api_status === "failed") {
                    // Check if trx_id is present despite error status
                    if (isset($api_data['trx_id']) || isset($api_data['id'])) {
                        $status = 'pending';
                        $response_msg = "Recharge is processing (Provider returned trx_id)";
                        
                        DB::table('sign_up')->where('id', $user_id)->decrement('voucher_balance', $amount);
                        DB::table('notifications')->insert([
                            'user_id' => $user_id,
                            'message' => "আপনার {$number} নম্বরে ৳{$amount} রিচার্জ প্রসেসিং আছে। (Txn: {$tran_id})",
                            'is_read' => 0,
                            'created_at' => $now
                        ]);
                    } else {
                        $status = 'failed';
                        $response_msg = $api_data['message'] ?? "Recharge Failed";
                    }
                }
            } else if (isset($api_data['trx_id'])) {
                $status = 'pending';
                $response_msg = "Recharge Sent (TRX ID: " . $api_data['trx_id'] . ")";
                DB::table('sign_up')->where('id', $user_id)->decrement('voucher_balance', $amount);
            }

            // Update final record
            DB::table('recharge_transactions')->where('tran_id', $tran_id)->update([
                'status' => $status,
                'api_response' => json_encode($api_data, JSON_UNESCAPED_UNICODE)
            ]);

            return response()->json([
                "success" => ($status !== 'failed'),
                "status" => ($status !== 'failed'),
                "message" => $response_msg,
                "tran_id" => $tran_id
            ]);

        } catch (\Exception $e) {
            \Log::error("Recharge Connection Error: " . $e->getMessage());
            $status = 'pending';
            
            DB::table('recharge_transactions')->where('tran_id', $tran_id)->update([
                'status' => $status,
                'api_response' => json_encode(["connection_error" => $e->getMessage()], JSON_UNESCAPED_UNICODE)
            ]);

            DB::table('sign_up')->where('id', $user_id)->decrement('voucher_balance', $amount);
            DB::table('notifications')->insert([
                'user_id' => $user_id,
                'message' => "আপনার রিচার্জ রিকোয়েস্টটি প্রসেস করা হচ্ছে। (Txn: {$tran_id})",
                'is_read' => 0,
                'created_at' => $now
            ]);

            return response()->json([
                "success" => true,
                "status" => true,
                "message" => "Recharge is being processed. Please wait.",
                "tran_id" => $tran_id
            ]);
        }
    }

    /**
     * Legacy Recharge History (get_recharge_history.php)
     */
    public function getRechargeHistory(Request $request)
    {
        $user_id = intval($request->query('user_id'));

        if (!$user_id) {
            return response()->json(["status" => false, "message" => "User ID required"]);
        }

        $history = DB::table('recharge_transactions')
            ->where('user_id', $user_id)
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($item) {
                $api_data = json_decode($item->api_response, true);
                $item->api_message = $api_data['api_response']['message'] ?? ($api_data['message'] ?? null);
                $item->provider_trx_id = $api_data['api_response']['trx_id'] ?? ($api_data['trx_id'] ?? null);
                return $item;
            });

        return response()->json([
            "success" => true,
            "status" => true,
            "rechargeHistory" => $history
        ]);
    }

    /**
     * Give Recharge Commission
     */
    private function giveRechargeCommission($userId, $amount, $tranId)
    {
        $user = DB::table('sign_up')->where('id', $userId)->first();
        if (!$user) return;

        $now = now()->toDateTimeString();
        $currentTime = now()->format("d-m-Y h:i A");

        // 1. Self Commission (1.5%)
        $selfComm = round($amount * 0.015, 2);
        if ($selfComm > 0) {
            DB::table('sign_up')->where('id', $userId)->increment('wallet_balance', $selfComm);
            DB::table('transactions')->insert([
                'user_id' => $userId,
                'refer_id' => $user->referCode,
                'amount' => $selfComm,
                'type' => 'commission',
                'payment_gateway' => 'Recharge Commission',
                'description' => "Recharge Commission for {$tranId}",
                'update_at' => $currentTime,
                'created_at' => $now,
                'date' => $now
            ]);
        }

        // 2. Upline Commission (2 levels, 0.05% each)
        $uplineComm = round($amount * 0.0005, 2);
        if ($uplineComm <= 0) return;

        $currentUplineReferCode = $user->referredBy;
        for ($i = 1; $i <= 2; $i++) {
            if (empty($currentUplineReferCode) || $currentUplineReferCode == "0") break;

            $upline = DB::table('sign_up')->where('referCode', $currentUplineReferCode)->first();
            if (!$upline) break;

            DB::table('sign_up')->where('id', $upline->id)->increment('wallet_balance', $uplineComm);
            DB::table('transactions')->insert([
                'user_id' => $upline->id,
                'refer_id' => $user->referCode,
                'amount' => $uplineComm,
                'type' => 'commission',
                'payment_gateway' => 'Recharge Team Commission',
                'description' => "Recharge Commission from {$user->name} (Level {$i})",
                'update_at' => $currentTime,
                'created_at' => $now,
                'date' => $now
            ]);

            $currentUplineReferCode = $upline->referredBy;
        }
    }
}
