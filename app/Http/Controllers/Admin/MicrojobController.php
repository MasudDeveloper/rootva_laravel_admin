<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Microjob;
use App\Models\MicrojobSubmission;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class MicrojobController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        
        $jobs = Microjob::select('microjobs.*', 'sign_up.name', 'sign_up.referCode')
            ->leftJoin('sign_up', 'microjobs.user_id', '=', 'sign_up.id')
            ->when($status, function($q) use ($status) {
                return $q->where('microjobs.status', $status);
            })
            ->orderBy('microjobs.id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('admin.microjobs.index', compact('jobs', 'status'));
    }

    public function viewSubmissions($job_id)
    {
        $job = Microjob::findOrFail($job_id);
        
        // Auto-approve logic: approve pending submissions older than 1 hour
        $this->processAutoApprovals($job_id);

        $submissions = MicrojobSubmission::with('user')
            ->where('job_id', $job_id)
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('admin.microjobs.submissions', compact('job', 'submissions'));
    }

    private function processAutoApprovals($job_id)
    {
        $oneHourAgo = now()->subHour();
        
        $pendingSubmissions = MicrojobSubmission::where('job_id', $job_id)
            ->where('status', 'pending')
            ->where('created_at', '<=', $oneHourAgo)
            ->get();

        foreach ($pendingSubmissions as $submission) {
            $this->approveSubmissionLogic($submission);
        }
    }

    public function approveSubmission($id)
    {
        $submission = MicrojobSubmission::findOrFail($id);
        if ($submission->status !== 'pending') {
            return back()->with('error', 'This submission has already been processed.');
        }

        $this->approveSubmissionLogic($submission);

        return back()->with('success', 'Submission approved successfully.');
    }

    private function approveSubmissionLogic($submission)
    {
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
                'payment_gateway' => 'Microjob',
                'description' => "Microjob Completed: {$job->title}",
                'update_at' => now()->format('d-m-Y, h:i A'),
                'created_at' => now()->toDateTimeString(),
                'date' => now()->toDateTimeString(),
            ]);
        });
    }

    public function rejectSubmission(Request $request, $id)
    {
        $submission = MicrojobSubmission::findOrFail($id);
        if ($submission->status !== 'pending') {
            return back()->with('error', 'This submission has already been processed.');
        }

        DB::transaction(function () use ($submission, $request) {
            $submission->update([
                'status' => 'rejected',
                'reject_reason' => $request->input('reason', 'Rejected by admin')
            ]);

            // Increment job target
            $submission->job()->increment('remaining_target');
        });

        return back()->with('success', 'Submission rejected successfully.');
    }

    public function create()
    {
        return view('admin.microjobs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount_per_worker' => 'required|numeric',
            'total_target' => 'required|integer',
            'job_url' => 'required|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $image_name = null;
        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $image_name = time() . "_" . $file->getClientOriginalName();
                
                // Absolute server path provided by user
                $path = '/home/syfoocuv/admin.rootvabd.com/service/microjobs/microjobImage';
                
                // Fallback for local development
                if (!file_exists('/home/syfoocuv')) {
                    $path = base_path('public/service/microjobs/microjobImage');
                }
                
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                
                $file->move($path, $image_name);
            } catch (\Exception $e) {
                return back()->with('error', 'Image upload failed: ' . $e->getMessage());
            }
        }

        Microjob::create([
            'user_id' => 0, // Admin posted
            'title' => $request->title,
            'description' => $request->description,
            'amount_per_worker' => $request->amount_per_worker,
            'total_target' => $request->total_target,
            'remaining_target' => $request->total_target,
            'total_amount' => $request->amount_per_worker * $request->total_target,
            'image_url' => $image_name,
            'job_url' => $request->job_url,
            'status' => 'approved',
            'is_active' => 1,
            'created_at' => now(),
        ]);

        return redirect()->route('admin.microjobs.index', ['status' => 'approved'])->with('success', 'Microjob created successfully.');
    }

    public function edit($id)
    {
        $job = Microjob::findOrFail($id);
        return view('admin.microjobs.edit', compact('job'));
    }

    public function updateJob(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount_per_worker' => 'required|numeric',
            'total_target' => 'required|integer',
            'job_url' => 'required|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'required|boolean',
        ]);

        $job = Microjob::findOrFail($id);
        
        $old_total = (int) $job->total_target;
        $new_total = (int) $request->total_target;
        $diff = $new_total - $old_total;

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'amount_per_worker' => $request->amount_per_worker,
            'total_target' => $new_total,
            'remaining_target' => $job->remaining_target + $diff,
            'job_url' => $request->job_url,
            'is_active' => $request->is_active,
            'updated_at' => now(),
        ];

        if ($request->hasFile('image')) {
            try {
                // Absolute server path provided by user
                $path = '/home/syfoocuv/admin.rootvabd.com/service/microjobs/microjobImage';
                
                // Fallback for local development
                if (!file_exists('/home/syfoocuv')) {
                    $path = base_path('public/service/microjobs/microjobImage');
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

        return redirect()->route('admin.microjobs.index', ['status' => $job->status])->with('success', 'Microjob updated successfully.');
    }

    public function destroy($id)
    {
        $job = Microjob::findOrFail($id);
        
        // Delete image if exists
        $path = '/home/syfoocuv/admin.rootvabd.com/service/microjobs/microjobImage';
        if (!file_exists('/home/syfoocuv')) {
            $path = public_path('service/microjobs/microjobImage');
        }

        if ($job->image_url && file_exists($path . '/' . $job->image_url)) {
            @unlink($path . '/' . $job->image_url);
        }

        $job->delete();

        return back()->with('success', 'Microjob deleted successfully.');
    }

    public function update(Request $request, $id)
    {
        $action = $request->input('action'); // 'approved' or 'rejected'
        $reason = $request->input('reject_reason');
        $job = Microjob::findOrFail($id);

        if ($job->status !== 'pending') {
            return back()->with('error', 'This job has already been processed.');
        }

        DB::transaction(function () use ($job, $action, $reason) {
            if ($action === 'approved') {
                $job->update([
                    'status' => 'approved',
                    'updated_at' => now()->toDateTimeString(),
                ]);
            } elseif ($action === 'rejected') {
                // Refund Money
                $user = SignUp::findOrFail($job->user_id);
                $user->increment('voucher_balance', $job->total_amount);

                // Delete Transaction if exists
                if ($job->transaction_id) {
                    Transaction::where('id', $job->transaction_id)->delete();
                }

                $job->update([
                    'status' => 'rejected',
                    'reject_reason' => $reason,
                    'updated_at' => now()->toDateTimeString(),
                ]);
            }
        });

        return back()->with('success', "Job has been {$action} successfully.");
    }
}
