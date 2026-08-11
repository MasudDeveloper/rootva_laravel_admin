<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SmmSubmission;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class SmmSubmissionController extends Controller
{
    /**
     * Display list of SMM Submissions for verification.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $submissions = SmmSubmission::with('user')
            ->where('status', $status)
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('admin.smm.index', compact('submissions', 'status'));
    }

    /**
     * Approve SMM Task and distribute payment.
     */
    public function approve($id)
    {
        $submission = SmmSubmission::findOrFail($id);

        if ($submission->status !== 'pending') {
            return back()->with('error', 'This submission has already been processed.');
        }

        DB::transaction(function () use ($submission) {
            $user = SignUp::findOrFail($submission->user_id);

            // 1. Update Submission status
            $submission->update(['status' => 'approved']);

            // 2. Increment User Wallet Balance
            $user->increment('wallet_balance', $submission->price);

            // 3. Log Transaction record
            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $submission->price,
                'type' => 'income',
                'payment_gateway' => 'SMM Selling',
                'description' => "Approved SMM Task: " . strtoupper($submission->task_type) . " Sell (" . $submission->input_field_1 . ")",
                'update_at' => now()->format('d-m-Y, h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', 'Submission approved and payment processed.');
    }

    /**
     * Reject SMM Task.
     */
    public function reject(Request $request, $id)
    {
        $submission = SmmSubmission::findOrFail($id);

        if ($submission->status !== 'pending') {
            return back()->with('error', 'This submission has already been processed.');
        }

        $feedback = $request->input('feedback', 'Rejected by admin due to incorrect details');
        $submission->update([
            'status' => 'rejected',
            'admin_feedback' => $feedback
        ]);

        return back()->with('success', 'Submission rejected.');
    }
}
