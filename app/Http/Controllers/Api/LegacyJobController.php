<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\SignUp;

class LegacyJobController extends Controller
{
    /**
     * Legacy Job Status (get_job_status.php)
     */
    public function getJobStatus()
    {
        $settings = DB::table('job_status')->first();
        if ($settings) {
            return response()->json([
                'facebook' => (int)$settings->facebook,
                'instagram' => (int)$settings->instagram,
                'email' => (int)$settings->email,
                'tiktok' => (int)$settings->tiktok,
                'review' => (int)$settings->review,
                'ads' => (int)$settings->ads,
                'dollar' => (int)$settings->dollar,
                'recharge' => (int)$settings->recharge,
                'sim_offer' => (int)$settings->sim_offer,
                'microjob' => (int)$settings->microjob,
                'job_post' => (int)$settings->job_post,
                'spin_bonus' => (int)$settings->spin_bonus,
                'math_game' => (int)$settings->math_game,
                'leadership' => (int)$settings->leadership,
                'daily_bonus' => (int)$settings->daily_bonus,
                'weekly_salary' => (int)$settings->weekly_salary,
                'monthly_salary' => (int)$settings->monthly_salary,
                'leaderboard' => (int)$settings->leaderboard,
                'reselling_shop' => (int)$settings->reselling_shop,
                'course' => (int)$settings->course,
                'freelancing_course' => (int)$settings->freelancing_course,
                'online_service' => (int)$settings->online_service
            ]);
        }
        return response()->json([
            'facebook' => 0,
            'instagram' => 0,
            'email' => 0,
            'tiktok' => 0,
            'review' => 0,
            'ads' => 0,
            'dollar' => 0,
            'recharge' => 0,
            'sim_offer' => 0,
            'microjob' => 0,
            'job_post' => 0,
            'spin_bonus' => 0,
            'math_game' => 0,
            'leadership' => 0,
            'daily_bonus' => 0,
            'weekly_salary' => 0,
            'monthly_salary' => 0,
            'leaderboard' => 0,
            'reselling_shop' => 0,
            'course' => 0,
            'freelancing_course' => 0,
            'online_service' => 0
        ]);
    }

    /**
     * Legacy Job Text (get_job_text.php)
     */
    public function getJobText(Request $request)
    {
        $type = $request->query('job_type') ?? $request->query('type');
        $text = DB::table('job_texts')->where('job_type', $type)->first();
        return response()->json([
            'content' => $text ? $text->content : '',
            'tutorial' => ''
        ]);
    }

    /**
     * Legacy Job Tutorial (get_job_tutorial.php)
     */
    public function getJobTutorial(Request $request)
    {
        $type = $request->query('job_type') ?? $request->query('type');
        $tutorial = DB::table('job_tutorials')->where('job_type', $type)->first();
        return response()->json([
            'content' => '',
            'tutorial' => $tutorial ? $tutorial->tutorial : ''
        ]);
    }

    /**
     * Legacy Math Answer (solve_math.php)
     */
    public function submitMathAnswer(Request $request)
    {
        $userId = $request->input('user_id');
        $amount = 0.50; // Example earning per math solve
        $currentTime = date("d-m-Y h:i A");

        $user = SignUp::find($userId);
        if ($user) {
            $user->increment('wallet_balance', $amount);
            
            Transaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => 'income',
                'payment_gateway' => 'Typing Job',
                'description' => 'Math solve income',
                'update_at' => $currentTime,
                'created_at' => $currentTime,
                'date' => now()
            ]);

            return response()->json(['success' => true, 'message' => 'Earning added']);
        }
        return response()->json(['success' => false, 'message' => 'User not found']);
    }
}
