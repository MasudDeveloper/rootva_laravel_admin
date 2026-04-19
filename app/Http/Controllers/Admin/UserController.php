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
        
        return view('admin.users.show', compact('user', 'transactions'));
    }

    public function update(Request $request, $id)
    {
        $user = SignUp::findOrFail($id);

        // Quick action: Only status change (e.g. Suspend from dropdown)
        if ($request->has('is_verified') && !$request->has('name')) {
            $user->update(['is_verified' => (int) $request->input('is_verified')]);
            $label = (int) $request->input('is_verified') === 4 ? 'suspended' : 'updated';
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
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
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
}
