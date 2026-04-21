<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalaryRequest;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class SalaryRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'Pending');
        
        $requests = SalaryRequest::select('salary_requests.*', 'sign_up.name', 'sign_up.number', 'sign_up.referCode')
            ->join('sign_up', 'salary_requests.user_id', '=', 'sign_up.id')
            ->when($status, function ($q) use ($status) {
                return $q->where('salary_requests.status', $status);
            })
            ->orderBy('salary_requests.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.salary_requests.index', compact('requests', 'status'));
    }

    public function approve($id)
    {
        $salaryRequest = SalaryRequest::findOrFail($id);
        
        if ($salaryRequest->status !== 'Pending') {
            return back()->with('error', 'Request already processed.');
        }

        try {
            DB::transaction(function () use ($salaryRequest) {
                // 1. Update User Balance (Credit 2000 TK)
                $user = SignUp::findOrFail($salaryRequest->user_id);
                $user->increment('wallet_balance', 2000);

                // 2. Log in Bonus Tracker
                DB::table('bonus_tracker')->insert([
                    'user_id' => $salaryRequest->user_id,
                    'bonus_type' => 'monthly_salary',
                    'amount' => 2000,
                    'created_at' => now()
                ]);

                // 3. Update Request Status
                $salaryRequest->update([
                    'status' => 'Approved',
                    'approved_at' => now(),
                ]);

                // 4. Log in Transactions (For Income History)
                Transaction::create([
                    'user_id' => $salaryRequest->user_id,
                    'amount' => 2000,
                    'type' => 'income',
                    'payment_gateway' => 'Monthly Salary',
                    'description' => 'মাসিক বেতন বোনাস আপনার ওয়ালেটে যোগ করা হয়েছে।',
                    'date' => now(),
                    'created_at' => now()->format('d-m-Y, h:i A'),
                    'update_at' => now()->format('d-m-Y, h:i A'),
                ]);
            });

            return back()->with('success', 'Salary request approved and 2000 TK credited to user wallet.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $salaryRequest = SalaryRequest::findOrFail($id);
        
        if ($salaryRequest->status !== 'Pending') {
            return back()->with('error', 'Request already processed.');
        }

        $salaryRequest->update([
            'status' => 'Rejected',
            'admin_note' => $request->input('admin_note'),
        ]);

        return back()->with('success', 'Salary request rejected.');
    }
}
