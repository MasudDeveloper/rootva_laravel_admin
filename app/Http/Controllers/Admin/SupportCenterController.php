<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMember;
use App\Models\SupportService;
use Illuminate\Support\Facades\File;

class SupportCenterController extends Controller
{
    public function index()
    {
        $members = SupportMember::orderBy('sort_order', 'asc')->get();
        $services = SupportService::orderBy('sort_order', 'asc')->get();
        return view('admin.support_center.index', compact('members', 'services'));
    }

    public function storeMember(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'designation' => 'required|string',
            'description' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'fb_link' => 'nullable|url',
            'wa_link' => 'nullable|url',
            'tg_link' => 'nullable|url',
            'phone_link' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $name = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('/uploads/support');
            
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            
            $image->move($destinationPath, $name);
            $avatarUrl = asset('uploads/support/' . $name);
        }

        SupportMember::create([
            'name' => $request->name,
            'designation' => $request->designation,
            'description' => $request->description,
            'avatar_url' => $avatarUrl,
            'fb_link' => $request->fb_link,
            'wa_link' => $request->wa_link,
            'tg_link' => $request->tg_link,
            'phone_link' => $request->phone_link,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Support member added successfully.');
    }

    public function destroyMember($id)
    {
        $member = SupportMember::findOrFail($id);
        if ($member->avatar_url) {
            $path = str_replace(asset(''), public_path(''), $member->avatar_url);
            if (File::exists($path)) {
                File::delete($path);
            }
        }
        $member->delete();
        return back()->with('success', 'Support member deleted successfully.');
    }

    public function storeService(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|url',
            'button_text' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $iconUrl = null;
        if ($request->hasFile('icon')) {
            $image = $request->file('icon');
            $name = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('/uploads/support');
            
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            
            $image->move($destinationPath, $name);
            $iconUrl = asset('uploads/support/' . $name);
        }

        SupportService::create([
            'name' => $request->name,
            'description' => $request->description,
            'icon_url' => $iconUrl,
            'link' => $request->link,
            'button_text' => $request->button_text ?? 'WhatsApp সাপোর্ট',
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Support service added successfully.');
    }

    public function destroyService($id)
    {
        $service = SupportService::findOrFail($id);
        if ($service->icon_url) {
            $path = str_replace(asset(''), public_path(''), $service->icon_url);
            if (File::exists($path)) {
                File::delete($path);
            }
        }
        $service->delete();
        return back()->with('success', 'Support service deleted successfully.');
    }
}
