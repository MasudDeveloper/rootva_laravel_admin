<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Microjob;
use App\Models\SignUp;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class LegacyMicrojobController extends Controller
{
    /**
     * Legacy Add Microjob (add_microjob.php / create_microjob.php)
     */
    public function addMicrojob(Request $request)
    {
        $user_id = $request->input('user_id');
        $title = $request->input('title');
        $description = $request->input('description');
        $amount_per_worker = $request->input('amount_per_worker');
        $total_target = $request->input('total_target');
        $job_url = $request->input('job_url');
        $total_amount = $request->input('total_amount');

        $current_time = now()->format('Y-m-d H:i:s');
        $current_time2 = now()->format('d-m-Y H:i A');

        $user = SignUp::find($user_id);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found']);
        }

        if ($user->voucher_balance < $total_amount) {
            return response()->json(['status' => 'error', 'message' => 'Insufficient balance']);
        }

        $image_name = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $image_name = time() . "_" . basename($file->getClientOriginalName());
            
            // Absolute server path provided by user
            $destPath = '/home/syfoocuv/admin.rootvabd.com/service/microjobs/microjobImage';
            
            // Fallback for local development
            if (!file_exists('/home/syfoocuv')) {
                $destPath = public_path('service/microjobs/microjobImage');
            }

            if (!file_exists($destPath)) {
                mkdir($destPath, 0777, true);
            }
            
            $file->move($destPath, $image_name);
        }

        try {
            DB::beginTransaction();

            // 1. Create Microjob
            $job = DB::table('microjobs')->insertGetId([
                'user_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'amount_per_worker' => $amount_per_worker,
                'total_target' => $total_target,
                'total_amount' => $total_amount,
                'image_url' => $image_name,
                'job_url' => $job_url,
                'remaining_target' => $total_target,
                'status' => 'Pending',
                'created_at' => $current_time
            ]);

            // 2. Insert into transactions
            $transaction_id = DB::table('transactions')->insertGetId([
                'user_id' => $user_id,
                'amount' => $total_amount,
                'payment_gateway' => 'Microjob Post',
                'type' => 'voucher_payment',
                'description' => 'Payment For Job Post',
                'update_at' => $current_time2,
                'created_at' => $current_time,
                'date' => $current_time
            ]);

            // 3. Deduct Balance
            DB::table('sign_up')
                ->where('id', $user_id)
                ->decrement('voucher_balance', $total_amount);

            // 4. Update Microjob with transaction_id
            DB::table('microjobs')
                ->where('id', $job)
                ->update(['transaction_id' => $transaction_id]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Microjob created'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Legacy Posted Jobs (get_posted_jobs.php)
     */
    public function getPostedJobs(Request $request)
    {
        $user_id = $request->query('user_id');
        
        if (!$user_id) {
            return response()->json([]);
        }

        $jobs = Microjob::where('user_id', $user_id)
            ->where('status', 'approved')
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($jobs);
    }

    /**
     * Legacy Job Submissions (get_job_submissions.php)
     */
    public function getJobSubmissions(Request $request)
    {
        $job_id = $request->query('job_id');
        
        if (!$job_id) {
            return response()->json([]);
        }

        $submissions = DB::table('microjob_submissions as s')
            ->join('sign_up as u', 's.worker_user_id', '=', 'u.id')
            ->where('s.job_id', $job_id)
            ->select('s.*', 'u.name', 'u.number')
            ->orderBy('s.created_at', 'desc')
            ->get();
            
        return response()->json($submissions);
    }

    /**
     * Legacy Update Submission Status (update_submission_status.php)
     */
    public function updateSubmissionStatus(Request $request)
    {
        $submission_id = $request->input('submission_id');
        $status = $request->input('status'); // approved or rejected
        $reject_reason = $request->input('reject_reason');

        if (!$submission_id || !$status) {
            return response()->json(['status' => 'error', 'message' => 'Missing parameters']);
        }

        $now = now()->toDateTimeString();
        $current_time = now()->format('d-m-Y h:i A');

        $sub = DB::table('microjob_submissions')->where('id', $submission_id)->first();
        if (!$sub) {
            return response()->json(['status' => 'error', 'message' => 'Invalid submission']);
        }

        $job = DB::table('microjobs')->where('id', $sub->job_id)->first();
        if (!$job) {
            return response()->json(['status' => 'error', 'message' => 'Invalid job']);
        }

        $amount = (double) $job->amount_per_worker;
        $worker_user_id = $sub->worker_user_id;

        try {
            DB::beginTransaction();

            if ($status == 'approved') {
                // Update submission
                DB::table('microjob_submissions')
                    ->where('id', $submission_id)
                    ->update(['status' => 'approved', 'reject_reason' => null]);

                // Update wallet_balance
                $user = SignUp::find($worker_user_id);
                if ($user) {
                    $user->increment('wallet_balance', $amount);

                    // Insert transaction
                    Transaction::create([
                        'user_id' => $worker_user_id,
                        'amount' => $amount,
                        'type' => 'income',
                        'payment_gateway' => 'Microjob',
                        'description' => "Earned from microjob ID: " . $sub->job_id,
                        'update_at' => $current_time,
                        'created_at' => $now,
                        'date' => $now
                    ]);
                }
            } else if ($status == 'rejected') {
                // Increase job target
                DB::table('microjobs')
                    ->where('id', $sub->job_id)
                    ->increment('remaining_target');

                // Update submission
                DB::table('microjob_submissions')
                    ->where('id', $submission_id)
                    ->update(['status' => 'rejected', 'reject_reason' => $reject_reason]);
            }

            DB::commit();
            return response()->json(['success' => true, 'status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Legacy Update Microjob Status (update_microjob_status.php)
     */
    public function updateMicrojobStatus(Request $request)
    {
        $job_id = $request->input('job_id');
        $status = $request->input('status'); // 0 or 1

        if ($job_id !== null && $status !== null) {
            DB::table('microjobs')
                ->where('id', $job_id)
                ->update(['is_active' => $status]);

            return response()->json([
                'success' => true,
                'message' => 'Job status updated successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid parameters'
        ]);
    }

    /**
     * Legacy User Microjob Posts (get_users_microjobs_posts.php)
     */
    public function getUserMicrojobsPosts(Request $request)
    {
        $user_id = $request->query('user_id');
        
        if (!$user_id) {
            return response()->json(['error' => true, 'message' => 'Invalid user ID']);
        }

        $jobs = Microjob::where('user_id', $user_id)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json([
            'error' => false,
            'microjobs' => $jobs
        ]);
    }

    /**
     * Legacy Microjobs List (get_microjobs.php / get_microjobs2.php)
     */
    public function getAllMicrojobs(Request $request)
    {
        $user_id = $request->input('user_id') ?? $request->query('user_id');

        if (empty($user_id)) {
            return response()->json(['success' => false, 'message' => 'User ID not provided']);
        }

        // Get job IDs already submitted by this user
        $submittedJobIds = DB::table('microjob_submissions')
            ->where('worker_user_id', $user_id)
            ->pluck('job_id');

        $jobs = Microjob::where('remaining_target', '>', 0)
            ->where('is_active', 1)
            ->where('status', 'approved')
            ->whereNotIn('id', $submittedJobIds)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($jobs);
    }

    /**
     * Legacy Submit Microjob (submit_microjob.php)
     */
    public function submitMicrojob(Request $request)
    {
        $job_id = $request->input('job_id');
        $worker_user_id = $request->input('user_id');
        $proof_message = $request->input('proof_message') ?? $request->input('message');
        $now = now()->toDateTimeString();

        if (!$job_id || !$worker_user_id) {
            return response()->json(["status" => "error", "message" => "Required parameters missing."]);
        }

        // 1. Check duplicate submission
        $check = DB::table('microjob_submissions')
            ->where('job_id', $job_id)
            ->where('worker_user_id', $worker_user_id)
            ->exists();

        if ($check) {
            return response()->json(["status" => "error", "message" => "You already submitted this job."]);
        }

        // 2. Check job availability
        $job = DB::table('microjobs')->where('id', $job_id)->first();
        if (!$job || $job->remaining_target <= 0) {
            return response()->json(["status" => "error", "message" => "This job is not available"]);
        }

        // 3. Handle proof image upload
        $proof_image_url = null;
        if ($request->hasFile('proof_image')) {
            $file = $request->file('proof_image');
            $fileName = time() . "_" . $file->getClientOriginalName();
            
            // Matches old API path: uploads/proofs/
            $destPath = public_path('uploads/proofs');
            if (!file_exists($destPath)) {
                mkdir($destPath, 0777, true);
            }
            
            $file->move($destPath, $fileName);
            $proof_image_url = 'uploads/proofs/' . $fileName;
        }

        try {
            DB::beginTransaction();

            // 4. Save submission
            DB::table('microjob_submissions')->insert([
                'worker_user_id' => $worker_user_id,
                'job_id' => $job_id,
                'proof_message' => $proof_message,
                'proof_image_url' => $proof_image_url,
                'status' => 'pending',
                'created_at' => $now
            ]);

            // 5. Update remaining target
            DB::table('microjobs')
                ->where('id', $job_id)
                ->decrement('remaining_target');

            DB::commit();

            return response()->json([
                "status" => "success", 
                "message" => "Job completed successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "status" => "error", 
                "message" => "Failed to submit job: " . $e->getMessage()
            ]);
        }
    }
}
