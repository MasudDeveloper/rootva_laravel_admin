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
            'facebook' => $request->has('facebook'),
            'instagram' => $request->has('instagram'),
            'email' => $request->has('email'),
            'tiktok' => $request->has('tiktok'),
            'review' => $request->has('review'),
            'ads' => $request->has('ads'),
            'dollar' => $request->has('dollar'),
            'recharge' => $request->has('recharge'),
            'sim_offer' => $request->has('sim_offer'),
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
