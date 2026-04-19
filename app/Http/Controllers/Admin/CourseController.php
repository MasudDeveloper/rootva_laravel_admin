<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::orderBy('id', 'desc')->paginate(25);
        $totalVideosCount = Course::count();
        
        // Stats for cards
        $stats = [
            'total_videos' => $totalVideosCount,
            'users_with_progress' => DB::table('course_progress_videos')->distinct('user_id')->count('user_id'),
            'bonuses_claimed' => DB::table('course_info')->where('claimed', 1)->count(),
        ];

        // User Progress Data
        $userProgress = DB::table('course_progress_videos')
            ->select('user_id', DB::raw('count(*) as completed_count'))
            ->where('is_complete', 1)
            ->groupBy('user_id')
            ->orderBy('completed_count', 'desc')
            ->get()
            ->map(function ($progress) use ($totalVideosCount) {
                $user = DB::table('sign_up')->where('id', $progress->user_id)->first(['name', 'referCode']);
                return (object)[
                    'user_id' => $progress->user_id,
                    'name' => $user->name ?? 'Unknown',
                    'referCode' => $user->referCode ?? 'N/A',
                    'completed' => $progress->completed_count,
                    'total' => $totalVideosCount,
                    'percent' => $totalVideosCount > 0 ? round(($progress->completed_count / $totalVideosCount) * 100) : 0
                ];
            });

        // Claimed Bonuses Data
        $claimedBonuses = DB::table('course_info')
            ->where('claimed', 1)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($claim) {
                $user = DB::table('sign_up')->where('id', $claim->user_id)->first(['name', 'referCode']);
                return (object)[
                    'user_id' => $claim->user_id,
                    'name' => $user->name ?? 'Unknown',
                    'referCode' => $user->referCode ?? 'N/A',
                    'amount' => $claim->bonus_amount,
                    'date' => $claim->created_at
                ];
            });

        return view('admin.courses.index', compact('courses', 'stats', 'userProgress', 'claimedBonuses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'youtube_url' => 'required|url',
        ]);

        Course::create([
            'title' => $request->title,
            'youtube_url' => $request->youtube_url,
            'duration' => $request->duration,
            'created_at' => now()->toDateTimeString(),
        ]);

        return back()->with('success', 'Course video added successfully!');
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $request->validate([
            'title' => 'required',
            'youtube_url' => 'required|url',
        ]);

        $course->update([
            'title' => $request->title,
            'youtube_url' => $request->youtube_url,
            'duration' => $request->duration,
        ]);

        return back()->with('success', 'Course video updated!');
    }

    public function destroy($id)
    {
        Course::findOrFail($id)->delete();
        return back()->with('success', 'Course video removed!');
    }
}
