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

        // Fetch task configs from DB (excluding the global notice settings row)
        $dbConfigs = \App\Models\SmmTaskConfig::where('task_type', '!=', 'global_notice')->get()->keyBy('task_type');
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

        // Get global marquee notice
        $globalNotice = \App\Models\SmmTaskConfig::find('global_notice');
        $globalNoticeText = $globalNotice ? $globalNotice->notice : 'রুটবা SMM পোর্টাল থেকে সরাসরি সাবমিট করে ইনকাম করুন ঝামেলা মুক্তভাবে!';

        // Get submission counts and total earnings for SMM
        $submissionsCount = SmmSubmission::where('user_id', $user->id)
            ->select('task_type', DB::raw('count(*) as total'), DB::raw('sum(CASE WHEN status="approved" THEN price ELSE 0 END) as income'))
            ->groupBy('task_type')
            ->get()
            ->keyBy('task_type');

        $analytics = [];
        $totalEarnings = 0;
        $taskTypes = ['gmail', 'facebook_cookies', 'facebook_zero_friend', 'facebook_number_id', 'instagram_2fa', 'instagram_cookies', 'whatsapp', 'telegram'];
        foreach ($taskTypes as $type) {
            $count = isset($submissionsCount[$type]) ? $submissionsCount[$type]->total : 0;
            $income = isset($submissionsCount[$type]) ? (double) $submissionsCount[$type]->income : 0.0;
            $analytics[$type] = [
                'count' => $count,
                'earnings' => $income
            ];
            $totalEarnings += $income;
        }

        // Calculate success rate and pending count across all submissions
        $totalSubmissions = SmmSubmission::where('user_id', $user->id)->count();
        $approvedSubmissions = SmmSubmission::where('user_id', $user->id)->where('status', 'approved')->count();
        $pendingSubmissionsCount = SmmSubmission::where('user_id', $user->id)->where('status', 'pending')->count();
        $successRate = $totalSubmissions > 0 ? round(($approvedSubmissions / $totalSubmissions) * 100, 2) : 100.0;

        // Get recent tasks list
        $recentTasks = SmmSubmission::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->take(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'number' => $user->number,
                'email' => $user->email,
                'wallet_balance' => (double) $user->wallet_balance,
                'profile_pic_url' => $user->profile_pic_url,
                'referCode' => $user->referCode,
                'is_verified' => (int) $user->is_verified,
            ],
            'rates' => $rates,
            'analytics' => $analytics,
            'total_smm_earnings' => $totalEarnings,
            'success_rate' => $successRate,
            'pending_count' => $pendingSubmissionsCount,
            'global_notice' => $globalNoticeText,
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
        $field3 = $request->input('field3');
        $field4 = $request->input('field4');

        if (empty($number) || empty($password) || empty($taskType) || empty($field1)) {
            return response()->json(['status' => 'error', 'message' => 'অবৈধ বা অসম্পূর্ণ তথ্য'], 400);
        }

        $user = SignUp::where('number', $number)->first();
        if (!$user || (!Hash::check($password, $user->password) && $password !== $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'অনুমতি নেই'], 401);
        }

        if ((int) $user->is_verified !== 1) {
            return response()->json(['status' => 'error', 'message' => 'দুঃখিত, শুধুমাত্র ভেরিফাইড মেম্বাররাই কাজ করতে পারবেন। অনুগ্রহ করে মূল রুটবা অ্যাপ থেকে আপনার অ্যাকাউন্টটি ভেরিফাই করুন।'], 403);
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
            'input_field_3' => $field3,
            'input_field_4' => $field4,
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
