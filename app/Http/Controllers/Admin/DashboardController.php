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

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_balance' => SignUp::sum('wallet_balance'),
            'demo_balance' => SignUp::where('is_verified', 3)->sum('wallet_balance'),
            'users' => [
                'total' => SignUp::count(),
                'verified' => SignUp::where('is_verified', 1)->count(),
                'pending' => SignUp::where('is_verified', 2)->count(),
                'unverified' => SignUp::where('is_verified', 0)->count(),
                'demo' => SignUp::where('is_verified', 3)->count(),
                'suspended' => SignUp::where('is_verified', 4)->count(),
            ],
            'pending_requests' => [
                'verification' => VerificationRequest::where('status', 'Pending')->count(),
                'money' => MoneyRequest::where('status', 'Pending')->count(),
                'withdraw' => WithdrawRequest::where('status', 'Pending')->count(),
                'microjobs' => Microjob::where('status', 'pending')->count(),
                'reselling' => Order::where('order_status', 'Pending')->count(),
                'sim_offers' => SimOfferRequest::where('status', 'pending')->count(),
                'services' => OnlineServiceOrder::where('status', 'pending')->count(),
                'salary' => SalaryRequest::where('status', 'Pending')->count(),
                'leadership' => LeadershipRewardRequest::where('status', 'Pending')->count(),
            ]
        ];

        $stats['real_balance'] = $stats['total_balance'] - $stats['demo_balance'];

        return view('admin.dashboard', compact('stats'));
    }

    public function getStatsJson()
    {
        $stats = [
            'total' => SignUp::count(),
            'unverified' => SignUp::where('is_verified', 0)->count(),
            'verified' => SignUp::where('is_verified', 1)->count(),
            'pending' => SignUp::where('is_verified', 2)->count(),
            'demo_verified' => SignUp::where('is_verified', 3)->count(),
            'suspand' => SignUp::where('is_verified', 4)->count(),
            'verification_requests' => VerificationRequest::where('status', 'Pending')->count(),
            'money_requests' => MoneyRequest::where('status', 'Pending')->count(),
            'withdraw_requests' => WithdrawRequest::where('status', 'Pending')->count(),
            'microjobs_requests' => Microjob::where('status', 'pending')->count(),
            'reselling_orders' => Order::where('order_status', 'Pending')->count(),
            'sim_requests' => SimOfferRequest::where('status', 'pending')->count(),
            'service_orders' => OnlineServiceOrder::where('status', 'pending')->count(),
            'salary_requests' => SalaryRequest::where('status', 'Pending')->count(),
            'leadership_requests' => LeadershipRewardRequest::where('status', 'Pending')->count(),
        ];

        return response()->json($stats);
    }
}
