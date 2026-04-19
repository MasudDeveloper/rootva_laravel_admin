<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Microjob;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class MicrojobController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'Pending');
        
        $jobs = Microjob::with('user')
            ->select('microjobs.*', 'sign_up.name', 'sign_up.referCode')
            ->join('sign_up', 'microjobs.user_id', '=', 'sign_up.id')
            ->when($status, function($q) use ($status) {
                return $q->where('microjobs.status', $status);
            })
            ->orderBy('microjobs.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.microjobs.index', compact('jobs', 'status'));
    }

    public function update(Request $request, $id)
    {
        $action = $request->input('action'); // 'Approved' or 'Rejected'
        $reason = $request->input('reject_reason');
        $job = Microjob::findOrFail($id);

        if ($job->status !== 'Pending') {
            return back()->with('error', 'This job has already been processed.');
        }

        DB::transaction(function () use ($job, $action, $reason) {
            if ($action === 'Approved') {
                $job->update([
                    'status' => 'Approved',
                    'updated_at' => now()->toDateTimeString(),
                ]);
            } elseif ($action === 'Rejected') {
                // Refund Money
                $user = SignUp::findOrFail($job->user_id);
                $user->increment('voucher_balance', $job->total_amount);

                // Delete Transaction if exists
                if ($job->transaction_id) {
                    Transaction::where('id', $job->transaction_id)->delete();
                }

                $job->update([
                    'status' => 'Rejected',
                    'reject_reason' => $reason,
                    'updated_at' => now()->toDateTimeString(),
                ]);
            }
        });

        return back()->with('success', "Job has been {$action} successfully.");
    }
}
