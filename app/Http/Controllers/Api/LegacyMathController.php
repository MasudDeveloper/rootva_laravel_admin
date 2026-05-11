<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class LegacyMathController extends Controller
{
    /**
     * Legacy Math Answer (solve_math.php)
     */
    public function submitMathAnswer(Request $request)
    {
        $userId = $request->input('user_id');
        $correct_answer = $request->input('correct_answer');
        $user_answer = $request->input('user_answer');

        $user = SignUp::find($userId);

        if ($user && (int)$user->math_game > 0) {
            
            $is_correct = ($correct_answer == $user_answer);

            // 1. Reduce math_game count
            $user->decrement('math_game', 1);

            if ($is_correct) {
                $amount = 1.00;
                $now = date("Y-m-d H:i:s");
                $now2 = date("d-m-Y h:i A");

                // 2. Add 1 tk
                $user->increment('wallet_balance', $amount);

                // 3. Log transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => '',
                    'amount' => $amount,
                    'type' => 'income',
                    'payment_gateway' => 'Typing Job',
                    'description' => 'Correct answer reward',
                    'update_at' => $now2,
                    'created_at' => $now2,
                    'date' => $now
                ]);

                return response()->json([
                    "status" => "correct", 
                    "message" => "সঠিক উত্তর! 1 টাকা পেয়েছেন।"
                ]);
            } else {
                return response()->json([
                    "status" => "wrong", 
                    "message" => "ভুল উত্তর। সুযোগ নষ্ট হয়েছে।"
                ]);
            }

        } else {
            return response()->json([
                "status" => "error", 
                "message" => "আপনার math_game সুযোগ নেই।"
            ]);
        }
    }

    /**
     * Legacy Math Income (get_math_income.php)
     */
    public function getMathIncome(Request $request)
    {
        $user_id = $request->input('user_id');

        if (!$user_id) {
            return response()->json(['error' => 'User ID is required']);
        }

        $total_amount = Transaction::where('user_id', $user_id)
            ->where('payment_gateway', 'Typing Job')
            ->sum('amount');

        return response()->json([
            'math_income' => (float)$total_amount
        ]);
    }
}
