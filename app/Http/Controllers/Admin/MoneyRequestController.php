<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\MoneyRequest;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class MoneyRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'Pending');
        
        $requests = MoneyRequest::with('user')
            ->select('money_requests.*', 'sign_up.name', 'sign_up.referCode')
            ->join('sign_up', 'money_requests.user_id', '=', 'sign_up.id')
            ->when($status, function($q) use ($status) {
                return $q->where('money_requests.status', $status);
            })
            ->orderBy('money_requests.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.money_requests.index', compact('requests', 'status'));
    }

    public function update(Request $request, $id)
    {
        $action = $request->input('action'); // 'Approved' or 'Rejected'
        $moneyRequest = MoneyRequest::findOrFail($id);

        if ($moneyRequest->status !== 'Pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($moneyRequest, $action) {
            if ($action === 'Approved') {
                // Update User Balance
                $user = SignUp::findOrFail($moneyRequest->user_id);
                $user->increment('wallet_balance', $moneyRequest->amount);

                // Log Transaction
                Transaction::create([
                    'user_id' => $user->id,
                    'refer_id' => $user->referCode,
                    'amount' => $moneyRequest->amount,
                    'type' => 'add',
                    'payment_gateway' => $moneyRequest->payment_gateway,
                    'description' => 'Add Money Approved',
                    'update_at' => now()->toDateTimeString(),
                    'created_at' => now()->toDateTimeString(),
                ]);
            }

            // Update Request Status
            $moneyRequest->update([
                'status' => $action,
                'updated_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', "Request has been {$action} successfully.");
    }
}
