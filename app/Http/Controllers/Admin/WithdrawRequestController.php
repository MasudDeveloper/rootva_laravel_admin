<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\WithdrawRequest;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class WithdrawRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'Pending');

        $requests = WithdrawRequest::select('withdraw_requests.*', 'sign_up.name', 'sign_up.referCode')
            ->join('sign_up', 'withdraw_requests.user_id', '=', 'sign_up.id')
            ->when($status, function ($q) use ($status) {
                return $q->where('withdraw_requests.status', $status);
            })
            ->orderBy('withdraw_requests.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.withdraw_requests.index', compact('requests', 'status'));
    }

    public function update(Request $request, $id)
    {
        $action = $request->input('action'); // 'Approved' or 'Rejected'
        $withdrawRequest = WithdrawRequest::findOrFail($id);

        if ($withdrawRequest->status !== 'Pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::transaction(function () use ($withdrawRequest, $action) {
            if ($action === 'Approved') {
                // Update Transaction Description
                Transaction::where('user_id', $withdrawRequest->user_id)
                    ->where('amount', $withdrawRequest->amount)
                    ->where('payment_gateway', $withdrawRequest->payment_gateway)
                    ->whereIn('type', ['withdraw', 'voucher_withdraw'])
                    ->update(['description' => 'Withdraw Request Approved']);
            } elseif ($action === 'Rejected') {
                // Delete Pending Transaction
                Transaction::where('user_id', $withdrawRequest->user_id)
                    ->where('amount', $withdrawRequest->amount)
                    ->where('payment_gateway', $withdrawRequest->payment_gateway)
                    ->whereIn('type', ['withdraw', 'voucher_withdraw'])
                    ->where('description', 'like', '%Pending%')
                    ->delete();

                // Refund Balance
                $user = SignUp::findOrFail($withdrawRequest->user_id);
                if ($withdrawRequest->balance_type === 'voucher') {
                    $user->increment('voucher_balance', $withdrawRequest->amount);
                } else {
                    $user->increment('wallet_balance', $withdrawRequest->amount);
                }
            }

            // Update Request Status
            $withdrawRequest->update([
                'status' => $action,
                'updated_at' => now()->toDateTimeString(),
            ]);

            // Create notification
            $message = $action === 'Approved' ? "অভিনন্দন! আপনার ৳{$withdrawRequest->amount} উইথড্র রিকোয়েস্ট অ্যাপ্রুভ হয়েছে।" : "দুঃখিত, আপনার ৳{$withdrawRequest->amount} উইথড্র রিকোয়েস্ট রিজেক্ট করা হয়েছে এবং ব্যালেন্স ফেরত দেওয়া হয়েছে।";
            \App\Models\Notification::create([
                'user_id' => $withdrawRequest->user_id,
                'message' => $message,
                'is_read' => 0,
                'created_at' => now()->format('d-m-Y h:i A')
            ]);
        });

        return back()->with('success', "Withdraw request has been {$action} successfully.");
    }
}
