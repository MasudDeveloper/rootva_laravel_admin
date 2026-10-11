<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VideoEarning;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class VideoEarningController extends Controller
{
    public function index()
    {
        $videos = VideoEarning::orderBy('serial', 'asc')->orderBy('id', 'asc')->paginate(20);
        
        $totalVideos = VideoEarning::count();
        $activeVideos = VideoEarning::where('status', 1)->count();
        $totalWatches = Transaction::where('payment_gateway', 'Video Earning')->count();
        $totalPaid = Transaction::where('payment_gateway', 'Video Earning')->sum('amount');

        $stats = [
            'total_videos' => $totalVideos,
            'active_videos' => $activeVideos,
            'total_watches' => $totalWatches,
            'total_paid' => (float)$totalPaid,
        ];

        return view('admin.video_earnings.index', compact('videos', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'youtube_url' => 'required|string',
            'duration' => 'nullable|integer|min:10',
            'serial' => 'nullable|integer',
        ]);

        $videoId = VideoEarning::extractVideoId($request->youtube_url);
        $duration = $request->input('duration') ? (int)$request->input('duration') : 60;
        
        $serial = $request->input('serial');
        if (empty($serial)) {
            $serial = (VideoEarning::max('serial') ?? 0) + 1;
        }

        VideoEarning::create([
            'title' => $request->title,
            'youtube_url' => $request->youtube_url,
            'video_id' => $videoId,
            'duration' => $duration,
            'serial' => $serial,
            'status' => $request->has('status') ? 1 : 1,
        ]);

        return back()->with('success', 'ভিডিও সফলভাবে যোগ করা হয়েছে!');
    }

    public function update(Request $request, $id)
    {
        $video = VideoEarning::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'youtube_url' => 'required|string',
            'duration' => 'nullable|integer|min:10',
            'serial' => 'nullable|integer',
        ]);

        $videoId = VideoEarning::extractVideoId($request->youtube_url) ?: $video->video_id;
        $duration = $request->input('duration') ? (int)$request->input('duration') : 60;
        $serial = $request->input('serial') ? (int)$request->input('serial') : $video->serial;

        $video->update([
            'title' => $request->title,
            'youtube_url' => $request->youtube_url,
            'video_id' => $videoId,
            'duration' => $duration,
            'serial' => $serial,
            'status' => $request->input('status', 1),
        ]);

        return back()->with('success', 'ভিডিও তথ্য আপডেট করা হয়েছে!');
    }

    public function destroy($id)
    {
        $video = VideoEarning::findOrFail($id);
        $video->delete();

        return back()->with('success', 'ভিডিও সফলভাবে মুছে ফেলা হয়েছে!');
    }

    public function toggleStatus($id)
    {
        $video = VideoEarning::findOrFail($id);
        $video->status = !$video->status;
        $video->save();

        return back()->with('success', 'ভিডিও স্ট্যাটাস পরিবর্তন করা হয়েছে!');
    }
}
