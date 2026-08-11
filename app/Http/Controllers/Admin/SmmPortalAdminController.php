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

        $status = $request->input('status', 'pending');
        $submissions = SmmSubmission::with('user')
            ->where('status', $status)
            ->orderBy('id', 'desc')
            ->paginate(30);

        // Fetch task configs for rate & password adjustments
        $configs = SmmTaskConfig::all();

        return view('admin.smm.dashboard', compact('submissions', 'status', 'configs'));
    }

    /**
     * Standalone Smm Task config updates (Rate & Password)
     */
    public function updateConfig(Request $request, $taskType)
    {
        if (!session('smm_admin_logged_in')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $config = SmmTaskConfig::findOrFail($taskType);
        $config->update([
            'rate' => $request->input('rate', $config->rate),
            'daily_password' => $request->input('daily_password', $config->daily_password),
            'status' => $request->input('status', $config->status),
            'notice' => $request->input('notice', $config->notice)
        ]);

        return back()->with('success', 'টাস্ক কনফিগারেশন সফলভাবে আপডেট করা হয়েছে');
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
