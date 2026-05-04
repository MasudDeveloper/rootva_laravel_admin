<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseVideo;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;

class LegacyCourseController extends Controller
{
    /**
     * Legacy Course List (get_courses.php)
     */
    public function getAllVideos(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID missing',
                'videos' => []
            ]);
        }

        // course_videos থেকে সব ভিডিও নেওয়া
        $videos = CourseVideo::orderBy('created_at', 'ASC')->get();

        if ($videos->count() > 0) {
            $formattedVideos = [];

            foreach ($videos as $video) {
                // প্রতিটি ভিডিওর জন্য ইউজারের প্রগ্রেস fetch করা
                $progress = DB::table('course_progress_videos')
                    ->where('user_id', $userId)
                    ->where('video_id', $video->id)
                    ->first();

                $formattedVideos[] = [
                    'id' => (int)$video->id,
                    'title' => $video->title,
                    'youtube_url' => $video->youtube_url,
                    'duration' => $this->durationToSeconds($video->duration),
                    'created_at' => $video->created_at,
                    'progress' => [
                        'is_complete' => $progress ? (int)$progress->is_complete : 0,
                        'watched_seconds' => $progress ? (int)$progress->watched_seconds : 0
                    ]
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Videos fetched successfully',
                'videos' => $formattedVideos
            ]);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'No videos found',
                'videos' => []
            ]);
        }
    }

    /**
     * Legacy Course Progress (get_course_progress.php)
     */
    public function getCourseProgress(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                "success" => false,
                "message" => "Missing user_id"
            ]);
        }

        // মোট ভিডিও সংখ্যা
        $totalVideos = CourseVideo::count();

        // ইউজারের প্রগ্রেস
        $progressData = DB::table('course_progress_videos')
            ->where('user_id', $userId)
            ->selectRaw('COUNT(*) as watched_count, SUM(is_complete) as complete_count')
            ->first();

        $watched_count = $progressData ? (int)$progressData->watched_count : 0;
        $complete_count = $progressData ? (int)$progressData->complete_count : 0;

        // প্রগ্রেস percentage হিসাব (পুরো ভিডিও দেখার ভিত্তিতে)
        $progress_percent = 0;
        if ($totalVideos > 0) {
            $progress_percent = round(($complete_count / $totalVideos) * 100);
        }

        $progress = [
            "total_videos" => (int)$totalVideos,
            "watched_videos" => $watched_count,
            "completed_videos" => $complete_count,
            "progress_percent" => $progress_percent
        ];

        return response()->json([
            "success" => true,
            "message" => "Course progress fetched successfully",
            "progress" => $progress
        ]);
    }

    /**
     * Update Course Progress (update_course_progress.php)
     */
    public function updateCourseProgress(Request $request)
    {
        $user_id = intval($request->input('user_id', 0));
        $video_id = intval($request->input('video_id', 0));
        $watched_seconds = intval($request->input('watched_seconds', 0));

        if ($user_id <= 0 || $video_id <= 0 || $watched_seconds < 0) {
            return response()->json(['error' => true, 'message' => 'Missing or invalid parameters']);
        }

        // ✅ ভিডিওর duration বের করা
        $video = CourseVideo::find($video_id);

        if (!$video) {
            return response()->json(['error' => true, 'message' => 'Video not found']);
        }

        // Duration string থেকে seconds এ কনভার্ট করা
        $total_duration = $this->durationToSeconds($video->duration);

        // ✅ চেক করা ভিডিও সম্পূর্ণ দেখা হয়েছে কিনা
        $is_complete = ($watched_seconds >= $total_duration) ? 1 : 0;

        // ✅ Progress আপডেট বা ক্রিয়েট করা
        DB::table('course_progress_videos')->updateOrInsert(
            ['user_id' => $user_id, 'video_id' => $video_id],
            [
                'watched_seconds' => $watched_seconds,
                'is_complete' => $is_complete,
                'updated_at' => now()
            ]
        );

        return response()->json([
            'error' => false,
            'message' => 'Progress updated successfully',
            'is_complete' => (int)$is_complete,
            'watched_seconds' => (int)$watched_seconds,
            'total_duration' => (int)$total_duration
        ]);
    }

    private function durationToSeconds($duration)
    {
        $parts = explode(':', $duration);
        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        } elseif (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        } else {
            return intval($duration);
        }
    }

    /**
     * Legacy Claim Course Bonus (claim_course_bonus.php)
     */
    public function claimCourseBonus(Request $request)
    {
        $userId = $request->input('user_id');
        $user = SignUp::find($userId);
        if ($user) {
            $user->increment('wallet_balance', 50); // Example bonus
            return response()->json([
                'success' => true, 
                'message' => 'বোনাস সফলভাবে আপনার ওয়ালেটে যোগ করা হয়েছে'
            ]);
        }
        return response()->json(['success' => false, 'message' => 'ইউজার পাওয়া যায়নি']);
    }

    /**
     * Legacy Update Course Completion (update_course_completion.php)
     */
    public function updateCourseCompletion(Request $request)
    {
        $user_id = intval($request->input('user_id', 0));
        $course_id = intval($request->input('course_id', 0));

        if ($user_id <= 0 || $course_id <= 0) {
            return response()->json(['error' => true, 'message' => 'Invalid user or course ID']);
        }

        try {
            return DB::transaction(function () use ($user_id, $course_id) {
                // 🧩 Step 0: Check if user already got spin bonus
                $spinResult = DB::table('wheel_spin_info')
                    ->where('user_id', $user_id)
                    ->select('claimed')
                    ->first();

                $hasSpinBonus = ($spinResult && $spinResult->claimed == 1);

                // 🧩 Step 1: মোট ভিডিও সংখ্যা বের করা
                $total = DB::table('course_videos')->count();

                if ($total == 0) {
                    throw new \Exception("No videos found for this course.");
                }

                // 🧩 Step 2: ইউজার কতগুলো ভিডিও সম্পূর্ণ করেছে
                $completed = DB::table('course_progress_videos')
                    ->where('user_id', $user_id)
                    ->where('is_complete', 1)
                    ->count();

                // 🧩 Step 3: শতকরা হিসাব
                $percent = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

                // 🧩 Step 4: কোর্স সম্পূর্ণ কিনা যাচাই করে আপডেট করা
                $status = "incomplete";
                $claimedValue = $hasSpinBonus ? 1 : 0;

                if ($completed >= $total) {
                    // ✅ কোর্স সম্পূর্ণ
                    DB::table('course_info')->updateOrInsert(
                        ['user_id' => $user_id, 'course_id' => $course_id],
                        ['completed' => 1, 'claimed' => $claimedValue, 'updated_at' => now()]
                    );
                    $status = "completed";
                } else {
                    // ⚠️ অসম্পূর্ণ
                    DB::table('course_info')->updateOrInsert(
                        ['user_id' => $user_id, 'course_id' => $course_id],
                        ['completed' => 0, 'claimed' => 0, 'updated_at' => now()]
                    );
                    $status = "incomplete";
                }

                return response()->json([
                    'error' => false,
                    'message' => 'Course progress updated successfully',
                    'status' => $status,
                    'completed_videos' => (int)$completed,
                    'total_videos' => (int)$total,
                    'percent' => $percent,
                    'claimed' => $hasSpinBonus ? 1 : 0
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
