<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SignUp;
use App\Models\VerificationRequest;
use App\Models\MoneyRequest;
use App\Models\WithdrawRequest;
use App\Models\Microjob;
use App\Models\Order;
use App\Models\SimOfferRequest;
use App\Models\OnlineServiceOrder;
use App\Models\SalaryRequest;
use App\Models\LeadershipRewardRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DashboardController extends Controller
{
    public function index()
    {
        $admin = auth()->user();
        $isSuperAdmin = $admin ? $admin->isSuperAdmin() : false;

        $stats = [
            'total_balance' => $isSuperAdmin ? SignUp::sum('wallet_balance') : 0,
            'demo_balance' => $isSuperAdmin ? SignUp::where('is_verified', 3)->sum('wallet_balance') : 0,
            'users' => [
                'total' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::count() : 0,
                'verified' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 1)->count() : 0,
                'pending' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 2)->count() : 0,
                'unverified' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 0)->count() : 0,
                'demo' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 3)->count() : 0,
                'suspended' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 4)->count() : 0,
                'active_now' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('last_active_at', '>=', now()->subMinutes(5))->count() : 0,
            ],
            'pending_requests' => [
                'verification' => ($isSuperAdmin || $admin->hasPermission('users')) ? VerificationRequest::where('status', 'Pending')->count() : 0,
                'money' => ($isSuperAdmin || $admin->hasPermission('withdrawals')) ? MoneyRequest::where('status', 'Pending')->count() : 0,
                'withdraw' => ($isSuperAdmin || $admin->hasPermission('withdrawals')) ? WithdrawRequest::where('status', 'Pending')->count() : 0,
                'microjobs' => ($isSuperAdmin || $admin->hasPermission('microjobs')) ? Microjob::where('status', 'pending')->count() : 0,
                'reselling' => ($isSuperAdmin || $admin->hasPermission('reselling')) ? Order::where('order_status', 'Pending')->count() : 0,
                'sim_offers' => ($isSuperAdmin || $admin->hasPermission('sim_offers')) ? SimOfferRequest::where('status', 'pending')->count() : 0,
                'services' => ($isSuperAdmin || $admin->hasPermission('courses')) ? OnlineServiceOrder::where('status', 'pending')->count() : 0,
                'salary' => ($isSuperAdmin || $admin->hasPermission('leadership')) ? SalaryRequest::where('status', 'Pending')->count() : 0,
                'leadership' => ($isSuperAdmin || $admin->hasPermission('leadership')) ? LeadershipRewardRequest::where('status', 'Pending')->count() : 0,
            ]
        ];

        $stats['real_balance'] = $stats['total_balance'] - $stats['demo_balance'];

        return view('admin.dashboard', compact('stats'));
    }

    public function getStatsJson()
    {
        $admin = auth()->user();
        $isSuperAdmin = $admin ? $admin->isSuperAdmin() : false;

        $stats = [
            'total' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::count() : 0,
            'unverified' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 0)->count() : 0,
            'verified' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 1)->count() : 0,
            'pending' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 2)->count() : 0,
            'demo_verified' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 3)->count() : 0,
            'suspand' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('is_verified', 4)->count() : 0,
            'active_now' => ($isSuperAdmin || $admin->hasPermission('users')) ? SignUp::where('last_active_at', '>=', now()->subMinutes(5))->count() : 0,
            'verification_requests' => ($isSuperAdmin || $admin->hasPermission('users')) ? VerificationRequest::where('status', 'Pending')->count() : 0,
            'money_requests' => ($isSuperAdmin || $admin->hasPermission('withdrawals')) ? MoneyRequest::where('status', 'Pending')->count() : 0,
            'withdraw_requests' => ($isSuperAdmin || $admin->hasPermission('withdrawals')) ? WithdrawRequest::where('status', 'Pending')->count() : 0,
            'microjobs_requests' => ($isSuperAdmin || $admin->hasPermission('microjobs')) ? Microjob::where('status', 'pending')->count() : 0,
            'reselling_orders' => ($isSuperAdmin || $admin->hasPermission('reselling')) ? Order::where('order_status', 'Pending')->count() : 0,
            'sim_requests' => ($isSuperAdmin || $admin->hasPermission('sim_offers')) ? SimOfferRequest::where('status', 'pending')->count() : 0,
            'service_orders' => ($isSuperAdmin || $admin->hasPermission('courses')) ? OnlineServiceOrder::where('status', 'pending')->count() : 0,
            'salary_requests' => ($isSuperAdmin || $admin->hasPermission('leadership')) ? SalaryRequest::where('status', 'Pending')->count() : 0,
            'leadership_requests' => ($isSuperAdmin || $admin->hasPermission('leadership')) ? LeadershipRewardRequest::where('status', 'Pending')->count() : 0,
        ];

        return response()->json($stats);
    }

    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return redirect()->back()->with('success', 'System cache cleared successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }
}
