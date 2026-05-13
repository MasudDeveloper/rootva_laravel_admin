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

    public function create()
    {
        return view('admin.review_jobs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount_per_worker' => 'required|numeric',
            'total_target' => 'required|integer',
            'review_url' => 'required|string',
            'scheduled_at' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $image_name = null;
        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $image_name = time() . "_" . $file->getClientOriginalName();
                
                // Absolute server path for review images
                $path = '/home/syfoocuv/admin.rootvabd.com/service/review_jobs/reviewJobImage';
                
                if (!file_exists('/home/syfoocuv')) {
                    $path = base_path('public/service/review_jobs/reviewJobImage');
                }
                
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                
                $file->move($path, $image_name);
            } catch (\Exception $e) {
                return back()->with('error', 'Image upload failed: ' . $e->getMessage());
            }
        }

        ReviewJob::create([
            'user_id' => 0,
            'title' => $request->title,
            'description' => $request->description,
            'amount_per_worker' => $request->amount_per_worker,
            'total_target' => $request->total_target,
            'remaining_target' => $request->total_target,
            'review_url' => $request->review_url,
            'scheduled_at' => $request->scheduled_at,
            'image_url' => $image_name, // If null, the model accessor will use the default image
            'created_at' => now(),
        ]);

        return redirect()->route('admin.review-jobs.index')->with('success', 'Review job created successfully.');
    }

    public function edit($id)
    {
        $job = ReviewJob::findOrFail($id);
        return view('admin.review_jobs.edit', compact('job'));
    }

    public function update(Request $request, $id)
    {
        $job = ReviewJob::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount_per_worker' => 'required|numeric',
            'total_target' => 'required|integer',
            'review_url' => 'required|string',
            'scheduled_at' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['title', 'description', 'amount_per_worker', 'total_target', 'review_url', 'scheduled_at']);

        if ($request->hasFile('image')) {
            try {
                $path = '/home/syfoocuv/admin.rootvabd.com/service/review_jobs/reviewJobImage';
                if (!file_exists('/home/syfoocuv')) {
                    $path = base_path('public/service/review_jobs/reviewJobImage');
                }

                // Delete old image if exists
                if ($job->image_url && file_exists($path . '/' . $job->image_url)) {
                    @unlink($path . '/' . $job->image_url);
                }

                $file = $request->file('image');
                $image_name = time() . "_" . $file->getClientOriginalName();
                
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                
                $file->move($path, $image_name);
                $data['image_url'] = $image_name;
            } catch (\Exception $e) {
                return back()->with('error', 'Image update failed: ' . $e->getMessage());
            }
        }

        $job->update($data);

        return redirect()->route('admin.review-jobs.index')->with('success', 'Review job updated successfully.');
    }

    public function destroy($id)
    {
        $job = ReviewJob::findOrFail($id);
        
        DB::transaction(function () use ($job) {
            // Delete submissions
            $job->submissions()->delete();
            
            // Delete image file if exists
            $path = '/home/syfoocuv/admin.rootvabd.com/service/review_jobs/reviewJobImage';
            if (!file_exists('/home/syfoocuv')) {
                $path = base_path('public/service/review_jobs/reviewJobImage');
            }
            if ($job->image_url && file_exists($path . '/' . $job->image_url)) {
                @unlink($path . '/' . $job->image_url);
            }
            
            // Delete job
            $job->delete();
        });

        return redirect()->route('admin.review-jobs.index')->with('success', 'Review job deleted successfully.');
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
