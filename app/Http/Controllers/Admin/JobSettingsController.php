<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobStatus;
use App\Models\JobText;
use App\Models\JobTutorial;

class JobSettingsController extends Controller
{
    public function index()
    {
        $status = JobStatus::first();
        $texts = JobText::all();
        $tutorials = JobTutorial::all();

        return view('admin.job_settings.index', compact('status', 'texts', 'tutorials'));
    }

    public function updateStatus(Request $request)
    {
        $status = JobStatus::first();
        
        $data = [
            'facebook' => $request->has('facebook') ? 1 : 0,
            'instagram' => $request->has('instagram') ? 1 : 0,
            'email' => $request->has('email') ? 1 : 0,
            'tiktok' => $request->has('tiktok') ? 1 : 0,
            'review' => $request->has('review') ? 1 : 0,
            'ads' => $request->has('ads') ? 1 : 0,
            'dollar' => $request->has('dollar') ? 1 : 0,
            'recharge' => $request->has('recharge') ? 1 : 0,
            'sim_offer' => $request->has('sim_offer') ? 1 : 0,
            'microjob' => $request->has('microjob') ? 1 : 0,
            'job_post' => $request->has('job_post') ? 1 : 0,
            'spin_bonus' => $request->has('spin_bonus') ? 1 : 0,
            'math_game' => $request->has('math_game') ? 1 : 0,
            'leadership' => $request->has('leadership') ? 1 : 0,
            'daily_bonus' => $request->has('daily_bonus') ? 1 : 0,
            'weekly_salary' => $request->has('weekly_salary') ? 1 : 0,
            'monthly_salary' => $request->has('monthly_salary') ? 1 : 0,
            'leaderboard' => $request->has('leaderboard') ? 1 : 0,
            'reselling_shop' => $request->has('reselling_shop') ? 1 : 0,
            'course' => $request->has('course') ? 1 : 0,
            'freelancing_course' => $request->has('freelancing_course') ? 1 : 0,
            'online_service' => $request->has('online_service') ? 1 : 0,
        ];

        $status->update($data);

        return back()->with('success', 'Job status settings updated successfully.');
    }

    public function updateTexts(Request $request)
    {
        foreach ($request->texts as $id => $content) {
            JobText::where('id', $id)->update(['content' => $content]);
        }

        return back()->with('success', 'Job texts updated successfully.');
    }

    public function updateTutorials(Request $request)
    {
        foreach ($request->tutorials as $id => $link) {
            JobTutorial::where('id', $id)->update(['tutorial' => $link]);
        }

        return back()->with('success', 'Job tutorials updated successfully.');
    }
}
