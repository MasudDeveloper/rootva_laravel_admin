<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = SignUp::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('referCode', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_verified', $status);
        }

        $users = $query->orderBy('id', 'desc')->paginate(50)->withQueryString();

        return view('admin.users.index', compact('users', 'search', 'status'));
    }

    public function show($id)
    {
        $user = SignUp::findOrFail($id);
        $transactions = Transaction::where('user_id', $id)->orderBy('id', 'desc')->paginate(20);
        
        // --- Calculate Leadership & Salary Stats safely ---
        $leadership = [
            'l1_verified' => 0,
            'rootva_progress' => 0,
            'rootva_achieved' => false,
            'l1_rootvas' => 0,
            'silver_progress' => 0,
            'silver_achieved' => false,
            'l1_silvers' => 0,
            'gold_progress' => 0,
            'gold_achieved' => false,
            'order_count' => 0,
            'diamond_achieved' => false,
            'top_achieved' => false,
        ];

        $salaryProgress = [
            'l1_verified' => 0,
            'l1_verified_progress' => 0,
            'l1_active' => 0,
            'l1_active_progress' => 0,
            'active_members' => [],
            'l2_verified' => 0,
            'l2_verified_progress' => 0,
            'orders' => 0,
            'orders_progress' => 0,
            'eligible' => false,
            'start_date' => '2000-01-01 00:00:00'
        ];

        try {
            if (!empty($user->referCode)) {
                $refQuote = DB::getPdo()->quote($user->referCode);

                // 1. Rootva Leader (L1 Verified count)
                $l1VerifiedCount = DB::table('sign_up as u1')
                    ->join('verification_requests as v', 'v.user_id', '=', 'u1.id')
                    ->where('u1.referredBy', $user->referCode)
                    ->where('v.status', 'Approved')
                    ->count();

                // 2. Silver Count (L1 members with >= 15 verified L2)
                $silverCount = DB::table(DB::raw("(
                    SELECT u2.referredBy
                    FROM sign_up u1
                    JOIN sign_up u2 ON u2.referredBy = u1.referCode
                    JOIN verification_requests v ON v.user_id = u2.id AND v.status = 'Approved'
                    WHERE u1.referredBy = {$refQuote}
                    GROUP BY u2.referredBy
                    HAVING COUNT(DISTINCT v.user_id) >= 15
                ) as sub"))->count();

                // 3. Gold Count (L1 members with >= 10 L2 Silver members)
                $goldSubQuery = DB::table(DB::raw("(
                    SELECT u2.referredBy as l1_code, u3.referredBy as l2_code
                    FROM sign_up u1
                    JOIN sign_up u2 ON u2.referredBy = u1.referCode
                    JOIN sign_up u3 ON u3.referredBy = u2.referCode
                    JOIN verification_requests v ON v.user_id = u3.id AND v.status = 'Approved'
                    WHERE u1.referredBy = {$refQuote}
                    GROUP BY u2.referredBy, u3.referredBy
                    HAVING COUNT(DISTINCT v.user_id) >= 15
                ) as l2_leaders"))
                ->select('l1_code', DB::raw('COUNT(DISTINCT l2_code) as silver_count'))
                ->groupBy('l1_code')
                ->havingRaw('COUNT(DISTINCT l2_code) >= 10')
                ->get();

                $goldCount = $goldSubQuery->count();

                // 4. Order count
                $orderCount = DB::table('orders')
                    ->where('user_id', $user->id)
                    ->whereIn('order_status', ['Confirmed', 'Delivered'])
                    ->count();

                $leadership = [
                    'l1_verified' => $l1VerifiedCount,
                    'rootva_progress' => min(100, ($l1VerifiedCount / 15) * 100),
                    'rootva_achieved' => $l1VerifiedCount >= 15,
                    'l1_rootvas' => $silverCount,
                    'silver_progress' => min(100, ($silverCount / 10) * 100),
                    'silver_achieved' => $silverCount >= 10,
                    'l1_silvers' => $goldCount,
                    'gold_progress' => min(100, ($goldCount / 10) * 100),
                    'gold_achieved' => $goldCount >= 10,
                    'order_count' => $orderCount,
                    'diamond_achieved' => ($goldCount >= 10 && $orderCount >= 3),
                    'top_achieved' => ($goldCount >= 10 && $orderCount >= 10),
                ];

                // --- Calculate Salary Progress via Fast SQL ---
                $lastBonus = DB::table('bonus_tracker')
                    ->where('user_id', $user->id)
                    ->where('bonus_type', 'monthly_salary')
                    ->orderBy('created_at', 'desc')
                    ->first();

                $salaryStartDate = $lastBonus ? $lastBonus->created_at : '2000-01-01 00:00:00';
                if (is_object($salaryStartDate)) {
                    $salaryStartDate = $salaryStartDate->toDateTimeString();
                }

                $salaryL1VerifiedCount = DB::table('sign_up as u1')
                    ->join('verification_requests as v', 'v.user_id', '=', 'u1.id')
                    ->where('u1.referredBy', $user->referCode)
                    ->where('v.status', 'Approved')
                    ->where(function($q) use ($salaryStartDate) {
                        $q->where('v.verified_raw_time', '>', $salaryStartDate)
                          ->orWhere('v.updated_at', '>', $salaryStartDate);
                    })
                    ->count();

                $salaryL2VerifiedTotal = DB::table('sign_up as u1')
                    ->join('sign_up as u2', 'u2.referredBy', '=', 'u1.referCode')
                    ->join('verification_requests as v', 'v.user_id', '=', 'u2.id')
                    ->where('u1.referredBy', $user->referCode)
                    ->where('v.status', 'Approved')
                    ->where(function($q) use ($salaryStartDate) {
                        $q->where('v.verified_raw_time', '>', $salaryStartDate)
                          ->orWhere('v.updated_at', '>', $salaryStartDate);
                    })
                    ->count();

                $activeRows = DB::table('sign_up as u1')
                    ->join('verification_requests as v1', 'v1.user_id', '=', 'u1.id')
                    ->join('sign_up as u2', 'u2.referredBy', '=', 'u1.referCode')
                    ->join('verification_requests as v2', 'v2.user_id', '=', 'u2.id')
                    ->where('u1.referredBy', $user->referCode)
                    ->where('v1.status', 'Approved')
                    ->where('v2.status', 'Approved')
                    ->select('u1.id', 'u1.name', 'u1.number', 'u1.referCode', DB::raw('COUNT(DISTINCT u2.id) as l2_count'))
                    ->groupBy('u1.id', 'u1.name', 'u1.number', 'u1.referCode')
                    ->havingRaw('COUNT(DISTINCT u2.id) >= 2')
                    ->get();

                $salaryL1Active = $activeRows->count();
                $salaryL1ActiveMembers = [];
                foreach ($activeRows as $r) {
                    $salaryL1ActiveMembers[] = [
                        'name' => $r->name ?? 'Unknown',
                        'number' => $r->number ?? 'N/A',
                        'refer_code' => $r->referCode,
                        'l2_count' => $r->l2_count
                    ];
                }

                $salaryOrders = DB::table('orders')
                    ->where('user_id', $user->id)
                    ->where('order_status', 'Delivered')
                    ->where('created_at', '>', $salaryStartDate)
                    ->count();

                $salaryProgress = [
                    'l1_verified' => $salaryL1VerifiedCount,
                    'l1_verified_progress' => min(100, ($salaryL1VerifiedCount / 30) * 100),
                    'l1_active' => $salaryL1Active,
                    'l1_active_progress' => min(100, ($salaryL1Active / 10) * 100),
                    'active_members' => $salaryL1ActiveMembers,
                    'l2_verified' => $salaryL2VerifiedTotal,
                    'l2_verified_progress' => min(100, ($salaryL2VerifiedTotal / 60) * 100),
                    'orders' => $salaryOrders,
                    'orders_progress' => min(100, ($salaryOrders / 1) * 100),
                    'eligible' => ($salaryL1VerifiedCount >= 30 && $salaryL2VerifiedTotal >= 60 && $salaryL1Active >= 10 && $salaryOrders >= 1),
                    'start_date' => $salaryStartDate
                ];
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error calculating leadership/salary stats for user {$user->id}: " . $e->getMessage());
        }
        
        return view('admin.users.show', compact('user', 'transactions', 'leadership', 'salaryProgress'));
    }

    public function update(Request $request, $id)
    {
        $user = SignUp::findOrFail($id);

        // Quick action: Only status change (e.g. Suspend from dropdown)
        if ($request->has('is_verified') && !$request->has('name')) {
            $newStatus = (int) $request->input('is_verified');
            $updateData = ['is_verified' => $newStatus];
            
            if ($user->is_verified != 1 && $newStatus == 1) {
                $updateData['verified_at'] = date("d-m-Y h:i A");
                $updateData['verified_raw_time'] = now()->toDateTimeString();
            }

            $user->update($updateData);
            $label = $newStatus === 4 ? 'suspended' : 'updated';
            return back()->with('success', "User has been {$label} successfully.");
        }
        
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email',
            'number'     => 'required|string',
            'is_verified'=> 'required|integer',
            'referredBy' => 'nullable|string',
            'password'   => 'nullable|string|min:4',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($user->is_verified != 1 && isset($data['is_verified']) && $data['is_verified'] == 1) {
            $data['verified_at'] = date("d-m-Y h:i A");
            $data['verified_raw_time'] = now()->toDateTimeString();
        }

        $user->update($data);

        return back()->with('success', 'User profile updated successfully.');
    }

    public function addMoney(Request $request, $id)
    {
        $user = SignUp::findOrFail($id);
        $amount = (float) $request->input('amount');
        $gateway = $request->input('payment_gateway', 'Admin Panel');
        $description = $request->input('description', 'Add Money by Admin');
        $giveCommission = $request->has('give_commission');

        // Prevent duplicate addition (same user, same amount, same gateway within the last 1 minute)
        $recentTxn = Transaction::where('user_id', $user->id)
            ->where('amount', $amount)
            ->where('payment_gateway', $gateway)
            ->where('type', 'income')
            ->where('created_at', '>=', now()->subMinutes(1))
            ->first();

        if ($recentTxn) {
            return back()->with('error', 'Duplicate request detected! This amount has already been added recently.');
        }

        DB::transaction(function () use ($user, $amount, $gateway, $description, $giveCommission) {
            $userAmount = $giveCommission ? ($amount * 0.90) : $amount;
            
            // 1. Update User Balance
            $user->increment('wallet_balance', $userAmount);

            // 2. Log Transaction
            Transaction::create([
                'user_id' => $user->id,
                'amount' => $userAmount,
                'type' => 'income',
                'payment_gateway' => $gateway,
                'description' => $description,
                'update_at' => now()->format('d-m-Y, h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);

            // 3. Referral Commission Logic (5 Levels)
            if ($giveCommission) {
                $commissionPerLevel = ($amount * 0.10) / 5;
                $currentUser = $user;

                for ($level = 1; $level <= 5; $level++) {
                    $uplineCode = $currentUser->referredBy;
                    if (!$uplineCode) break;

                    $upline = SignUp::where('referCode', $uplineCode)->first();
                    if (!$upline) break;

                    // Log Commission
                    Transaction::create([
                        'user_id' => $upline->id,
                        'refer_id' => $currentUser->referCode,
                        'amount' => $commissionPerLevel,
                        'type' => 'commission',
                        'payment_gateway' => 'system',
                        'description' => "Level $level commission from $gateway",
                        'update_at' => now()->format('d-m-Y, h:i A'),
                        'created_at' => now()->toDateTimeString(),
                    ]);

                    $upline->increment('wallet_balance', $commissionPerLevel);
                    $currentUser = $upline;
                }
            }
        });

        return back()->with('success', 'Balance added successfully.');
    }

    public function withdrawMoney(Request $request, $id)
    {
        $user = SignUp::findOrFail($id);
        $amount = (float) $request->input('amount');

        if ($user->wallet_balance < $amount) {
            return back()->with('error', 'Insufficient balance.');
        }

        DB::transaction(function () use ($user, $amount, $request) {
            $user->decrement('wallet_balance', $amount);

            Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'withdraw',
                'payment_gateway' => 'Admin Panel',
                'description' => $request->input('description', 'Direct withdrawal by Admin'),
                'update_at' => now()->format('d-m-Y, h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', 'Balance withdrawn successfully.');
    }

    /**
     * High Balance Users / Top Wallet Holders List
     */
    public function topHolders(Request $request)
    {
        $minBalance = $request->input('min_balance', '1000');
        $verType = $request->input('ver_type', 'real_verified'); // ডিফল্ট রিয়েল ভেরিফাইড (is_verified = 1)
        $search = $request->input('search');

        $query = SignUp::query();

        // ১. ভেরিফিকেশন টাইপ ফিল্টার (ডেমো বাদ দিয়ে বা রিয়েল ভেরিফাইড)
        if ($verType === 'real_verified') {
            $query->where('is_verified', 1);
        } elseif ($verType === 'no_demo') {
            $query->where('is_verified', '!=', 3);
        } elseif ($verType === 'demo_only') {
            $query->where('is_verified', 3);
        }

        // ২. ব্যালেন্স ফিল্টার
        if ($minBalance !== 'all' && $minBalance !== null && $minBalance !== '') {
            $query->where('wallet_balance', '>=', (float) $minBalance);
        }

        // ৩. সার্চ ফিল্টার
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('referCode', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('refer_id', 'like', "%{$search}%");
            });
        }

        // সর্বোচ্চ যার কাছে আছে ওইটা সবার উপরে থাকবে (Order by wallet_balance DESC)
        $users = $query->orderBy('wallet_balance', 'desc')->paginate(50)->withQueryString();

        // স্ট্যাটিস্টিকস এর জন্যও একই ভেরিফিকেশন ফিল্টার প্রয়োগ
        $statsQuery = SignUp::query();
        if ($verType === 'real_verified') {
            $statsQuery->where('is_verified', 1);
        } elseif ($verType === 'no_demo') {
            $statsQuery->where('is_verified', '!=', 3);
        } elseif ($verType === 'demo_only') {
            $statsQuery->where('is_verified', 3);
        }

        $stats = [
            'total_1k' => (clone $statsQuery)->where('wallet_balance', '>=', 1000)->count(),
            'total_5k' => (clone $statsQuery)->where('wallet_balance', '>=', 5000)->count(),
            'total_10k' => (clone $statsQuery)->where('wallet_balance', '>=', 10000)->count(),
            'total_balance_1k' => (clone $statsQuery)->where('wallet_balance', '>=', 1000)->sum('wallet_balance'),
        ];

        return view('admin.users.top_holders', compact('users', 'minBalance', 'verType', 'search', 'stats'));
    }

    public function transferVoucher(Request $request, $id)
    {
        $user = SignUp::findOrFail($id);
        $amount = (float) $request->input('amount');

        if ($amount <= 0) {
            return back()->with('error', 'Invalid transfer amount.');
        }

        if ($user->voucher_balance < $amount) {
            return back()->with('error', 'Insufficient voucher balance.');
        }

        DB::transaction(function () use ($user, $amount) {
            // Decrement voucher and increment wallet
            $user->decrement('voucher_balance', $amount);
            $user->increment('wallet_balance', $amount);

            // Log Transaction for Voucher deduction
            Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'voucher_withdraw',
                'payment_gateway' => 'Voucher Transfer',
                'description' => 'Transfer Voucher Balance to Main Wallet',
                'update_at' => now()->format('d-m-Y, h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);

            // Log Transaction for Main balance addition
            Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'income',
                'payment_gateway' => 'Voucher Transfer',
                'description' => 'Received from Voucher Balance',
                'update_at' => now()->format('d-m-Y, h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', 'Voucher balance successfully transferred to main balance.');
    }

    public function addDemoOrder(Request $request, $id)
    {
        $user = SignUp::findOrFail($id);
        
        $request->validate([
            'product_name' => 'required|string|max:255',
            'product_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'created_at' => 'required|date',
        ]);

        $product_price = (float) $request->input('product_price');
        $quantity = (int) $request->input('quantity');
        $total_price = $product_price * $quantity;
        
        $customDate = $request->input('created_at');
        // append current time to the custom date to make a full timestamp
        $createdAtTimestamp = date('Y-m-d H:i:s', strtotime($customDate . ' ' . date('H:i:s')));

        \App\Models\Order::create([
            'user_id' => $user->id,
            'product_id' => 99999, // Demo product ID
            'product_name' => $request->input('product_name'),
            'product_price' => $product_price,
            'customer_name' => 'Demo Customer',
            'customer_number' => '01700000000',
            'quantity' => $quantity,
            'total_price' => $total_price,
            'total_earning' => 0,
            'total_product_price' => $total_price,
            'delivery_charge' => 0,
            'customer_address' => 'Demo Address',
            'account_number' => '01700000000',
            'transaction_id' => 99999,
            'payment_gateway' => 'Demo Gateway',
            'amount' => 0,
            'created_at' => $createdAtTimestamp,
            'order_status' => 'Confirmed'
        ]);

        return back()->with('success', 'Demo order added successfully and is confirmed for salary/leadership.');
    }
}
