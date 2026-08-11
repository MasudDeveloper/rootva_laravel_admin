<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\SmmSubmission;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SmmJobController extends Controller
{
    /**
     * Get user details and current active SMM rates/notices.
     */
    public function getStatus(Request $request)
    {
        $number = $request->input('number');
        $password = $request->input('password');

        if (empty($number) || empty($password)) {
            return response()->json(['status' => 'error', 'message' => 'অবৈধ ডেটা'], 400);
        }

        $user = SignUp::where('number', $number)->first();
        if (!$user || (!Hash::check($password, $user->password) && $password !== $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'ইউজার আইডি অথবা পাসওয়ার্ড ভুল'], 401);
        }

        // Fetch task configs from DB
        $dbConfigs = \App\Models\SmmTaskConfig::all()->keyBy('task_type');
        $rates = [];
        foreach ($dbConfigs as $type => $conf) {
            $rates[$type] = [
                'name' => $conf->name,
                'rate' => (double) $conf->rate,
                'status' => $conf->status,
                'notice' => $conf->notice,
                'video_url' => $conf->video_url ?? '',
                'daily_password' => $conf->daily_password ?? '',
                'required_fields' => $conf->required_fields ?? []
            ];
        }

        // Get submission counts and total earnings for SMM
        $submissionsCount = SmmSubmission::where('user_id', $user->id)
            ->select('task_type', DB::raw('count(*) as total'), DB::raw('sum(CASE WHEN status="approved" THEN price ELSE 0 END) as income'))
            ->groupBy('task_type')
            ->get()
            ->keyBy('task_type');

        $analytics = [];
        $totalEarnings = 0;
        foreach (['gmail', 'facebook', 'instagram', 'whatsapp', 'telegram'] as $type) {
            $count = isset($submissionsCount[$type]) ? $submissionsCount[$type]->total : 0;
            $income = isset($submissionsCount[$type]) ? (double) $submissionsCount[$type]->income : 0.0;
            $analytics[$type] = [
                'count' => $count,
                'earnings' => $income
            ];
            $totalEarnings += $income;
        }

        // Get recent tasks list
        $recentTasks = SmmSubmission::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'number' => $user->number,
                'wallet_balance' => (double) $user->wallet_balance,
            ],
            'rates' => $rates,
            'analytics' => $analytics,
            'total_smm_earnings' => $totalEarnings,
            'recent_submissions' => $recentTasks
        ]);
    }

    /**
     * Submit an SMM Task work proof.
     */
    public function submitTask(Request $request)
    {
        $number = $request->input('number');
        $password = $request->input('password');
        $taskType = $request->input('task_type');
        $field1 = $request->input('field1');
        $field2 = $request->input('field2');

        if (empty($number) || empty($password) || empty($taskType) || empty($field1)) {
            return response()->json(['status' => 'error', 'message' => 'অবৈধ বা অসম্পূর্ণ তথ্য'], 400);
        }

        $user = SignUp::where('number', $number)->first();
        if (!$user || (!Hash::check($password, $user->password) && $password !== $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'অনুমতি নেই'], 401);
        }

        $config = \App\Models\SmmTaskConfig::find($taskType);

        if (!$config || $config->status !== 'active' || $config->rate <= 0) {
            return response()->json(['status' => 'error', 'message' => 'এই কাজটি বর্তমানে বন্ধ রয়েছে'], 400);
        }

        // Check daily limit (e.g. 10 submissions max per day)
        $todaySubmissions = SmmSubmission::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        if ($todaySubmissions >= 10) {
            return response()->json(['status' => 'error', 'message' => 'আজকের দৈনিক সাবমিশন লিমিট (১০/১০) অতিক্রম করেছেন'], 400);
        }

        $submission = SmmSubmission::create([
            'user_id' => $user->id,
            'task_type' => $taskType,
            'input_field_1' => $field1,
            'input_field_2' => $field2,
            'price' => $config->rate,
            'status' => 'pending'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'কাজটি সফলভাবে সাবমিট করা হয়েছে। অ্যাডমিন ভেরিফাই করে ব্যালেন্স যোগ করবেন।',
            'submission' => $submission
        ]);
    }
}
