<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SmmSubmission;
use App\Models\SmmTaskConfig;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SmmPortalAdminController extends Controller
{
    /**
     * SMM Dedicated Admin Login Page
     */
    public function showLogin()
    {
        return view('admin.smm.login');
    }

    /**
     * Handle SMM Dedicated Admin Login Authentication
     */
    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        // Simple high-secure standalone credential matching admin/smm (can be verified from configs)
        if ($username === 'admin' && $password === 'rootvasmm2026') {
            session(['smm_admin_logged_in' => true]);
            return redirect()->route('admin.smm.dashboard');
        }

        return back()->with('error', 'ভুল অ্যাডমিন আইডি অথবা পাসওয়ার্ড');
    }

    /**
     * SMM Standalone Dashboard & Submissions List
     */
    public function dashboard(Request $request)
    {
        if (!session('smm_admin_logged_in')) {
            return redirect()->route('admin.smm.login');
        }

        $selectedPayoutMonth = $request->input('payout_month', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedPayoutMonth)) {
            $selectedPayoutMonth = date('Y-m');
        }

        // Handle Monthly CSV Export
        if ($request->input('action') === 'export_monthly_payout_csv') {
            $monthData = SmmSubmission::with('user')
                ->where('status', 'approved')
                ->where(function ($q) use ($selectedPayoutMonth) {
                    $q->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$selectedPayoutMonth]);
                })
                ->orderBy('id', 'desc')
                ->get();

            $filename = "smm_monthly_payouts_" . $selectedPayoutMonth . "_" . date('Ymd_His') . ".csv";
            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Expires"             => "0"
            ];

            return response()->stream(function () use ($monthData) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($file, [
                    'Submission ID', 'User Name', 'User Phone', 'User Refer Code', 
                    'Referred By', 'Task Type', 'Account / Username', 'Task Price (TK)', 
                    'Net User Payout 90% (TK)', 'Referral Commission 10% (TK)', 'Status', 'Approved At / Date'
                ]);

                foreach ($monthData as $row) {
                    $p = (double)$row->price;
                    fputcsv($file, [
                        $row->id,
                        $row->user ? $row->user->name : 'N/A',
                        $row->user ? $row->user->number : 'N/A',
                        $row->user ? $row->user->referCode : 'N/A',
                        $row->user ? ($row->user->referredBy ?: 'System') : 'System',
                        strtoupper($row->task_type),
                        $row->input_field_1,
                        number_format($p, 2, '.', ''),
                        number_format($p * 0.90, 2, '.', ''),
                        number_format($p * 0.10, 2, '.', ''),
                        strtoupper($row->status),
                        $row->updated_at ?: $row->created_at
                    ]);
                }
                fclose($file);
            }, 200, $headers);
        }

        // 1. Overall stats
        $statPending = SmmSubmission::where('status', 'pending')->count();
        $statApproved = SmmSubmission::where('status', 'approved')->count();
        $statRejected = SmmSubmission::where('status', 'rejected')->count();
        $statTotalPayout = (double)SmmSubmission::where('status', 'approved')->sum('price');

        // 2. Today's Approved Payout
        $todayStr = date('Y-m-d');
        $statTodayPayout = (double)SmmSubmission::where('status', 'approved')
            ->whereDate(DB::raw('COALESCE(updated_at, created_at)'), $todayStr)
            ->sum('price');
        $statTodayCount = SmmSubmission::where('status', 'approved')
            ->whereDate(DB::raw('COALESCE(updated_at, created_at)'), $todayStr)
            ->count();

        // 3. This Month's Approved Payout
        $thisMonthStr = date('Y-m');
        $statThisMonthPayout = (double)SmmSubmission::where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$thisMonthStr])
            ->sum('price');
        $statThisMonthCount = SmmSubmission::where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$thisMonthStr])
            ->count();

        // 4. Last Month's Approved Payout
        $lastMonthStr = date('Y-m', strtotime('first day of -1 month'));
        $statLastMonthPayout = (double)SmmSubmission::where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$lastMonthStr])
            ->sum('price');
        $statLastMonthCount = SmmSubmission::where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$lastMonthStr])
            ->count();

        // 5. Selected Month Breakdown
        $selectedMonthTotal = (double)SmmSubmission::where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$selectedPayoutMonth])
            ->sum('price');
        $selectedMonthCount = SmmSubmission::where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$selectedPayoutMonth])
            ->count();
        $selectedMonthUsers = SmmSubmission::where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$selectedPayoutMonth])
            ->distinct('user_id')
            ->count('user_id');

        $selectedMonthTaskBreakdown = SmmSubmission::select('task_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as total'))
            ->where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$selectedPayoutMonth])
            ->groupBy('task_type')
            ->orderByDesc('total')
            ->get();

        $selectedMonthDailyBreakdown = SmmSubmission::select(DB::raw('DATE(COALESCE(updated_at, created_at)) as day_date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as total'))
            ->where('status', 'approved')
            ->whereRaw("DATE_FORMAT(COALESCE(updated_at, created_at), '%Y-%m') = ?", [$selectedPayoutMonth])
            ->groupBy('day_date')
            ->orderByDesc('day_date')
            ->get();

        $status = $request->input('status', 'pending');
        $submissions = SmmSubmission::with('user')
            ->where('status', $status)
            ->orderBy('id', 'desc')
            ->paginate(30);

        // Fetch task configs for rate & password adjustments
        $configs = SmmTaskConfig::where('task_type', '!=', 'global_notice')->get();
        $globalNotice = SmmTaskConfig::find('global_notice');

        return view('admin.smm.dashboard', compact(
            'submissions', 'status', 'configs', 'globalNotice',
            'statPending', 'statApproved', 'statRejected', 'statTotalPayout',
            'statTodayPayout', 'statTodayCount',
            'statThisMonthPayout', 'statThisMonthCount',
            'statLastMonthPayout', 'statLastMonthCount',
            'selectedPayoutMonth', 'selectedMonthTotal', 'selectedMonthCount', 'selectedMonthUsers',
            'selectedMonthTaskBreakdown', 'selectedMonthDailyBreakdown'
        ));
    }

    /**
     * Standalone Smm Task config updates (Rate & Password & Dynamic Fields)
     */
    public function updateConfig(Request $request, $taskType)
    {
        if (!session('smm_admin_logged_in')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $config = SmmTaskConfig::findOrFail($taskType);

        $fields = null;
        if ($request->has('field_labels')) {
            $fields = [];
            foreach ($request->field_labels as $index => $label) {
                if (!empty(trim($label))) {
                    $fields[] = [
                        'key' => 'field_' . ($index + 1),
                        'label' => trim($label),
                        'type' => $request->field_types[$index] ?? 'text',
                        'required' => isset($request->field_required[$index]) && $request->field_required[$index] == '1'
                    ];
                }
            }
        }

        $data = [
            'name' => $request->input('name', $config->name),
            'rate' => $request->input('rate', $config->rate),
            'daily_password' => $request->input('daily_password', $config->daily_password),
            'video_url' => $request->input('video_url', $config->video_url),
            'status' => $request->input('status', $config->status),
            'notice' => $request->input('notice', $config->notice)
        ];

        if ($fields !== null) {
            $data['required_fields'] = $fields;
        }

        $config->update($data);

        return back()->with('success', 'টাস্ক কনফিগারেশন সফলভাবে আপডেট করা হয়েছে');
    }

    /**
     * Store new SMM project task config
     */
    public function storeTask(Request $request)
    {
        if (!session('smm_admin_logged_in')) {
            return redirect()->route('admin.smm.login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
        ]);

        $taskType = \Illuminate\Support\Str::slug($request->input('task_type_input') ?: $request->input('name'), '_');

        if (empty($taskType)) {
            $taskType = 'task_' . time();
        }

        if (SmmTaskConfig::where('task_type', $taskType)->exists()) {
            $taskType = $taskType . '_' . rand(100, 999);
        }

        $fields = [];
        if ($request->has('field_labels')) {
            foreach ($request->field_labels as $index => $label) {
                if (!empty(trim($label))) {
                    $fields[] = [
                        'key' => 'field_' . ($index + 1),
                        'label' => trim($label),
                        'type' => $request->field_types[$index] ?? 'text',
                        'required' => isset($request->field_required[$index]) && $request->field_required[$index] == '1'
                    ];
                }
            }
        }

        SmmTaskConfig::create([
            'task_type' => $taskType,
            'name' => $request->input('name'),
            'rate' => $request->input('rate', 0),
            'status' => $request->input('status', 'active'),
            'notice' => $request->input('notice'),
            'video_url' => $request->input('video_url'),
            'daily_password' => $request->input('daily_password'),
            'required_fields' => $fields
        ]);

        return back()->with('success', 'নতুন SMM প্রজেক্ট সফলভাবে তৈরি করা হয়েছে!');
    }

    /**
     * Delete an SMM task config
     */
    public function deleteTask($taskType)
    {
        if (!session('smm_admin_logged_in')) {
            return redirect()->route('admin.smm.login');
        }

        if ($taskType === 'global_notice') {
            return back()->with('error', 'Global notice cannot be deleted.');
        }

        $config = SmmTaskConfig::where('task_type', $taskType)->first();
        if ($config) {
            $config->delete();
            return back()->with('success', 'প্রজেক্টটি সফলভাবে মুছে ফেলা হয়েছে।');
        }

        return back()->with('error', 'প্রজেক্টটি খুঁজে পাওয়া যায়নি।');
    }

    /**
     * SMM Dedicated Logout
     */
    public function logout()
    {
        session()->forget('smm_admin_logged_in');
        return redirect()->route('admin.smm.login');
    }
}
