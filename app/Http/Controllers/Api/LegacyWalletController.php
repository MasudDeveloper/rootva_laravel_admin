<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class LegacyWalletController extends Controller
{
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
        $credits = Transaction::where('user_id', $userId)
            ->whereIn('type', ['add', 'commission', 'income'])
            ->sum('amount');

        // Debit types: withdraw, payment
        $debits = Transaction::where('user_id', $userId)
            ->whereIn('type', ['withdraw', 'payment'])
            ->sum('amount');

        $totalBalance = $credits - $debits;

        // Update wallet balance in the database
        $user->wallet_balance = $totalBalance;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'ব্যালেন্স সফলভাবে আপডেট হয়েছে',
            'wallet_balance' => round((double)$totalBalance, 2),
            'voucher_balance' => round((double)($user->voucher_balance ?? 0), 2),
            'math_income' => (int)Transaction::where('user_id', $userId)->where('payment_gateway', 'Typing Job')->sum('amount')
        ]);
    }

    /**
     * Legacy Transaction History (get_transaction_history.php)
     */
    public function getTransactionHistory(Request $request)
    {
        $user_id = $request->query('user_id') ?? $request->input('user_id');
        $limit = (int) ($request->query('limit') ?? 50);
        $offset = (int) ($request->query('offset') ?? 0);

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
                ->offset($offset)
                ->limit($limit)
                ->get();

            return response()->json([
                'transactions' => $transactions
            ]);
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

        $calculate = function($condition = "") use ($userId) {
            $sql = "SELECT amount FROM transactions 
                    WHERE user_id = ? AND LOWER(type) IN ('income', 'commission') $condition";
            
            $results = DB::select($sql, [$userId]);
            $total_amount = 0;
            foreach ($results as $row) {
                $total_amount += (float)$row->amount;
            }

            return [
                'total' => number_format((float)$total_amount, 2, '.', ''),
                'details' => []
            ];
        };

        $now = now();
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();
        $last_7_days = $now->copy()->subDays(6)->toDateString();
        $last_30_days = $now->copy()->subDays(29)->toDateString();
        $last_365_days = $now->copy()->subDays(364)->toDateString();

        return response()->json([
            'status' => 'success',
            'message' => 'Income report fetched successfully',
            'data' => [
                'today' => $calculate("AND DATE(date) = '$today'"),
                'yesterday' => $calculate("AND DATE(date) = '$yesterday'"),
                'week' => $calculate("AND DATE(date) BETWEEN '$last_7_days' AND '$today'"),
                'month' => $calculate("AND DATE(date) BETWEEN '$last_30_days' AND '$today'"),
                'year' => $calculate("AND DATE(date) BETWEEN '$last_365_days' AND '$today'"),
                'total' => $calculate("")
            ]
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Legacy Income History (get_income_history.php)
     */
    public function getIncomeHistory(Request $request)
    {
        $user_id = $request->query('user_id') ?? $request->input('user_id');
        $limit = (int) ($request->query('limit') ?? 50);
        $offset = (int) ($request->query('offset') ?? 0);

        if ($user_id) {
            $transactions = Transaction::where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction history fetched successfully',
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
     * Legacy Voucher Balance (get_voucher_balance.php)
     */
    public function getVoucherBalance(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);

        if ($user) {
            return response()->json([
                'success' => true,
                'wallet_balance' => (double)$user->wallet_balance,
                'voucher_balance' => (double)$user->voucher_balance
            ]);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    /**
     * Legacy Convert to Voucher (convert_to_voucher.php)
     */
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

        DB::beginTransaction();
        try {
            $user->update([
                'wallet_balance' => $newWallet,
                'voucher_balance' => $newVoucher
            ]);

            $currentTime = now()->format("d-m-Y h:i A");
            $now = now()->toDateTimeString();

            // 1. Transaction for Wallet deduction
            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $amount,
                'type' => 'voucher_payment',
                'payment_gateway' => 'Wallet',
                'description' => 'Voucher Balance Convert',
                'update_at' => $currentTime,
                'created_at' => $currentTime,
                'date' => $now
            ]);

            // 2. Transaction for Voucher addition
            Transaction::create([
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

            DB::commit();

            return response()->json([
                'success' => true,
                'converted_amount' => $finalAmount,
                'charge' => $charge,
                'wallet_balance' => $newWallet,
                'voucher_balance' => $newVoucher
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'ব্যালেন্স আপডেট ব্যর্থ হয়েছে']);
        }
    }


}
