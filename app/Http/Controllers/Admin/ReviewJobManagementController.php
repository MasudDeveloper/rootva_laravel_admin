<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\ReviewJob;
use App\Models\ReviewSubmission;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ReviewJobManagementController extends Controller
{
    public function index()
    {
        $jobs = ReviewJob::withCount(['submissions' => function($q) {
            $q->where('status', 'pending');
        }])->orderBy('id', 'desc')->paginate(25);

        return view('admin.review_jobs.index', compact('jobs'));
    }

    public function submissions($job_id)
    {
        $job = ReviewJob::findOrFail($job_id);
        $submissions = ReviewSubmission::with('user')
            ->where('job_id', $job_id)
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('admin.review_jobs.submissions', compact('job', 'submissions'));
    }

    public function approve($id)
    {
        $submission = ReviewSubmission::findOrFail($id);
        
        if ($submission->status !== 'pending') {
            return back()->with('error', 'This submission has already been processed.');
        }

        DB::transaction(function () use ($submission) {
            $job = $submission->job;
            $user = SignUp::findOrFail($submission->worker_user_id);

            // 1. Update Submission status
            $submission->update(['status' => 'approved']);

            // 2. Increment User Wallet
            $user->increment('wallet_balance', $job->amount_per_worker);

            // 3. Log Transaction
            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $job->amount_per_worker,
                'type' => 'income',
                'payment_gateway' => 'Review Job',
                'description' => "Review Job Completed: {$job->title}",
                'update_at' => now()->format('d-m-Y, h:i A'),
                'created_at' => now()->toDateTimeString(),
            ]);
        });

        return back()->with('success', 'Submission approved and payment processed.');
    }

    public function reject(Request $request, $id)
    {
        $submission = ReviewSubmission::findOrFail($id);
        $job_id = $submission->job_id;

        // In legacy, reject deletes the submission and unlocks the job
        DB::transaction(function () use ($submission) {
            // Unlock job (if needed, legacy sets locked_by to null)
            $submission->job()->update(['locked_by' => null]);
            
            // Delete submission
            $submission->delete();
        });

        return redirect()->route('admin.review-jobs.submissions', $job_id)
            ->with('success', 'Submission rejected and removed.');
    }
}
