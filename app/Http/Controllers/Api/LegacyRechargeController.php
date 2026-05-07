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
        $now = now()->toDateTimeString();

        // ✅ Validation checks
        if (!$user_id || empty($number) || empty($operator) || $amount <= 0) {
            return response()->json(["status" => false, "message" => "Invalid Request"]);
        }

        if (strlen($number) != 11) {
            return response()->json(["status" => false, "message" => "Invalid mobile number"]);
        }

        if ($amount < 20) {
            return response()->json(["success" => false, "status" => false, "message" => "Minimum recharge amount is 20 Taka"]);
        }

        // ✅ Generate Transaction ID
        // ✅ Generate Transaction ID (matching the format in demo)
        $tran_id = 'TXN_' . time() . rand(10, 99);

        // ✅ Check Balance Before API Call
        $user = DB::table('sign_up')->where('id', $user_id)->first();
        if (!$user || $user->voucher_balance < $amount) {
            return response()->json(["status" => false, "message" => "Insufficient voucher balance"]);
        }

        // ✅ Step 1: Insert initial pending transaction
        $initial_response = json_encode(["stage" => "initiated", "tran_id" => $tran_id], JSON_UNESCAPED_UNICODE);
        
        DB::table('recharge_transactions')->insert([
            'user_id' => $user_id,
            'number' => $number,
            'operator' => $operator,
            'amount' => $amount,
            'tran_id' => $tran_id,
            'status' => 'pending',
            'api_response' => $initial_response,
            'created_at' => $now
        ]);

        // ✅ Step 2: Send Recharge Request to Info-Uddokta API
        $api_key = "b757dc1768aa0e76fbafaee2be7ec307";
        $api_url = "https://info-uddokta.com/telecom/api/recharge.php";

        try {
            \Log::info("Recharge Request: " . $api_url . "?" . http_build_query([
                "key" => $api_key,
                "number" => $number,
                "amount" => $amount,
                "operator" => $operator,
                "id" => $tran_id
            ]));

            $response = Http::timeout(60)->get($api_url, [
                "key" => $api_key,
                "number" => $number,
                "amount" => $amount,
                "operator" => $operator,
                "id" => $tran_id
            ]);

            \Log::info("Recharge Response: " . $response->body());
            $api_data = $response->json();

            if (!$api_data) {
                DB::table('recharge_transactions')->where('tran_id', $tran_id)->update([
                    'status' => 'failed',
                    'api_response' => $response->body()
                ]);
                return response()->json(["status" => false, "message" => "Invalid API Response", "response" => $response->body()]);
            }

            // ✅ Step 4: Handle API success/failure
            $status = 'pending';
            $response_msg = "Recharge Request Sent";

            if (isset($api_data['status'])) {
                if ($api_data['status'] === "success") {
                    $status = 'success';
                    $response_msg = "Recharge Successful";
                    $msg = "আপনার {$number} নম্বরে ৳{$amount} রিচার্জ সফল হয়েছে (Txn: {$tran_id})";

                    // ✅ Wallet deduct
                    DB::table('sign_up')->where('id', $user_id)->decrement('voucher_balance', $amount);

                    // ✅ Add notification
                    DB::table('notifications')->insert([
                        'user_id' => $user_id,
                        'message' => $msg,
                        'is_read' => 0,
                        'created_at' => $now
                    ]);
                } elseif ($api_data['status'] === "error") {
                    $status = 'failed';
                    $response_msg = $api_data['message'] ?? "Recharge Failed";
                }
            }

            // ✅ Step 5: Update final transaction record
            DB::table('recharge_transactions')->where('tran_id', $tran_id)->update([
                'status' => $status,
                'api_response' => json_encode(["api_response" => $api_data], JSON_UNESCAPED_UNICODE)
            ]);

            // ✅ Step 6: Return response to app
            return response()->json([
                "success" => ($status === 'success'),
                "status" => ($status === 'success'),
                "message" => $response_msg,
                "tran_id" => $tran_id,
                "response" => json_encode($api_data, JSON_UNESCAPED_UNICODE)
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            DB::table('recharge_transactions')->where('tran_id', $tran_id)->update([
                'status' => 'failed',
                'api_response' => json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE)
            ]);
            return response()->json(["status" => false, "message" => "API Connection Failed", "error" => $e->getMessage()]);
        }
    }

    public function rechargeSuccessHandler(Request $request)
    {
        $user_id = (int) $request->input('user_id');
        $amount  = (float) $request->input('amount');
        $operator = trim($request->input('operator', ''));
        $tran_id = $request->input('tran_id');
        $number  = $request->input('number');
        $now     = now()->toDateTimeString();
        $current_time = date('d-m-Y, h:i A');

        if (!$user_id || !$amount || !$tran_id || !$number) {
            return response()->json(["status" => false, "message" => "Invalid payload"]);
        }

        try {
            return DB::transaction(function () use ($user_id, $amount, $operator, $tran_id, $number, $now, $current_time) {
                // ✅ Step 1: Insert success transaction
                DB::table('recharge_transactions')->insert([
                    'user_id' => $user_id,
                    'number' => $number,
                    'operator' => $operator,
                    'amount' => $amount,
                    'tran_id' => $tran_id,
                    'status' => 'success',
                    'created_at' => $now
                ]);

                // 2️⃣ Deduct wallet
                DB::table('sign_up')->where('id', $user_id)->decrement('voucher_balance', $amount);

                // 3️⃣ Transaction log
                $desc = "Recharge ৳$amount to $number (Txn: $tran_id)";
                DB::table('transactions')->insert([
                    'user_id' => $user_id,
                    'amount' => $amount,
                    'type' => 'recharge',
                    'payment_gateway' => 'Voucher',
                    'description' => $desc,
                    'update_at' => $current_time,
                    'created_at' => $current_time,
                    'date' => $now
                ]);

                // 4️⃣ Send Commission (1.5%)
                $commission_total = $amount * 0.015; // 1.5%
                $self_desc = "Self commission from Mobile Recharge";
                
                DB::table('transactions')->insert([
                    'user_id' => $user_id,
                    'refer_id' => null,
                    'amount' => $commission_total,
                    'type' => 'commission',
                    'payment_gateway' => 'system',
                    'description' => $self_desc,
                    'update_at' => $current_time,
                    'created_at' => $current_time,
                    'date' => $now
                ]);

                // Update user's wallet
                DB::table('sign_up')->where('id', $user_id)->increment('wallet_balance', $commission_total);

                // 5️⃣ Notification
                $msg = "আপনার {$number} নম্বরে ৳{$amount} রিচার্জ সফল হয়েছে";
                DB::table('notifications')->insert([
                    'user_id' => $user_id,
                    'message' => $msg,
                    'is_read' => 0,
                    'created_at' => $now
                ]);

                // 6️⃣ Push notification
                $user = DB::table('sign_up')->where('id', $user_id)->first(['fcm_token']);
                if ($user && $user->fcm_token) {
                    $this->sendFCMNotification($user->fcm_token, "Recharge Complete", $msg);
                }

                return response()->json(["status" => true, "message" => "Recharge completed"]);
            });
        } catch (\Exception $e) {
            return response()->json(["status" => false, "error" => $e->getMessage()]);
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
     * Legacy Recharge Alternative (recharge.php)
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

        $tran_id = 'TXN_' . time() . rand(10, 99);
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

        // Info-Uddokta API Call
        $api_key = "b757dc1768aa0e76fbafaee2be7ec307";
        $api_url = "https://info-uddokta.com/telecom/api/recharge.php";

        $response = Http::get($api_url, [
            "key" => $api_key,
            "number" => $request->input('number'),
            "amount" => $amount,
            "operator" => $request->input('operator'),
            "id" => $tran_id
        ]);

        $api_data = $response->json();
        $status = 'failed';
        if ($response->successful() && isset($api_data['status']) && $api_data['status'] === 'success') {
            $status = 'success';
        }
        if ($status === 'failed') {
            $user->increment('voucher_balance', $amount);
        }

        RechargeTransaction::where('tran_id', $tran_id)->update(['status' => $status, 'api_response' => $response->body()]);

        return response()->json(["status" => ($status === 'success'), "message" => ($status === 'success' ? "Recharge successful" : "Recharge failed"), "tran_id" => $tran_id]);
    }
}
