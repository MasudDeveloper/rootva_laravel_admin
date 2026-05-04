<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReviewJob;
use App\Models\ReviewSubmission;
use Illuminate\Support\Facades\DB;

class LegacyReviewJobController extends Controller
{
    /**
     * Legacy Review Job Endpoints
     */

    public function getAvailableReviewJobs(Request $request)
    {
        $userId = $request->input('user_id');
        
        // Fetch jobs that are not locked by others, or locked by this user, and have remaining target
        $jobs = ReviewJob::where('remaining_target', '>', 0)
            ->where(function($query) use ($userId) {
                $query->whereNull('locked_by')
                      ->orWhere('locked_by', 0)
                      ->orWhere('locked_by', $userId);
            })
            ->get();
            
        return response()->json($jobs);
    }

    public function getLockReviewJobs(Request $request)
    {
        $userId = $request->input('user_id');
        $jobs = ReviewJob::where('locked_by', $userId)->get();
        return response()->json($jobs);
    }

    public function getReviewJobSocial()
    {
        $socials = DB::table('review_job_socials')->orderBy('id', 'desc')->first();
        
        if ($socials) {
            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'লিংক সফলভাবে পাওয়া গেছে',
                'review_job_socials' => $socials
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'লিংক পাওয়া যায়নি'
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function lockReviewJob(Request $request)
    {
        $userId = $request->input('user_id');
        $jobId = $request->input('job_id');
        
        $job = ReviewJob::find($jobId);
        if ($job) {
            if ($job->locked_by && $job->locked_by != $userId) {
                return response()->json(['success' => false, 'message' => 'Job already locked by another user']);
            }
            
            $job->update([
                'locked_by' => $userId,
                'scheduled_at' => now()
            ]);
            return response()->json(['success' => true, 'message' => 'Job locked successfully']);
        }
        return response()->json(['success' => false, 'message' => 'Job not found']);
    }

    public function unlockReviewJob(Request $request)
    {
        $jobId = $request->input('job_id');
        $job = ReviewJob::find($jobId);
        if ($job) {
            $job->update([
                'locked_by' => null,
                'scheduled_at' => null
            ]);
            return response()->json(['success' => true, 'message' => 'Job unlocked']);
        }
        return response()->json(['success' => false, 'message' => 'Job not found']);
    }

    public function submitReviewJobProof(Request $request)
    {
        $user_id = $request->input('user_id');
        $refer_id = $request->input('refer_id');
        $job_id = $request->input('job_id');
        $message = $request->input('message');
        $number = $request->input('number');

        if (!$user_id || !$refer_id || !$job_id || empty($message) || empty($number)) {
            return response()->json([
                'success' => false,
                'status' => 'fail',
                'message' => 'Missing required fields!'
            ]);
        }

        $image_url = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = uniqid("proof_", true) . "." . $file->getClientOriginalExtension();
            $file->move(public_path('reviewJobImage'), $fileName);
            $image_url = 'reviewJobImage/' . $fileName;
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'No image uploaded or upload error!'
            ]);
        }

        try {
            ReviewSubmission::create([
                'job_id' => $job_id,
                'refer_id' => $refer_id,
                'worker_user_id' => $user_id,
                'proof_image_url' => $image_url,
                'proof_message' => $message,
                'number' => $number,
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Proof uploaded and saved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'fail',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
}
