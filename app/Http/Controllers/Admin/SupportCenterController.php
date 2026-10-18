<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMember;
use App\Models\SupportService;
use App\Models\PaymentNumber;
use App\Models\SocialLink;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class SupportCenterController extends Controller
{
    public function index()
    {
        $members = SupportMember::orderBy('sort_order', 'asc')->get();
        $services = SupportService::orderBy('sort_order', 'asc')->get();
        $banglaQrSupportLink = PaymentNumber::value('bangla_qr_support_link') ?? DB::table('social_links')->value('bangla_qr_support_link') ?? '';
        return view('admin.support_center.index', compact('members', 'services', 'banglaQrSupportLink'));
    }

    public function updateBanglaQrLink(Request $request)
    {
        $link = $request->input('bangla_qr_support_link');

        if (Schema::hasTable('payment_number') && !Schema::hasColumn('payment_number', 'bangla_qr_support_link')) {
            try {
                Schema::table('payment_number', function ($table) {
                    $table->string('bangla_qr_support_link', 255)->nullable()->after('bangla_qr');
                });
            } catch (\Throwable $ignored) {}
        }

        if (Schema::hasTable('social_links') && !Schema::hasColumn('social_links', 'bangla_qr_support_link')) {
            try {
                Schema::table('social_links', function ($table) {
                    $table->string('bangla_qr_support_link', 255)->nullable()->after('support_verify');
                });
            } catch (\Throwable $ignored) {}
        }

        $payment = PaymentNumber::first();
        if ($payment) {
            $payment->update(['bangla_qr_support_link' => $link]);
        }

        $social = SocialLink::first();
        if ($social) {
            $social->update(['bangla_qr_support_link' => $link]);
        }

        Cache::forget('api_social_links');

        return back()->with('success', 'বাংলা কিউআর সাপোর্ট সেন্টার লিঙ্ক সফলভাবে আপডেট করা হয়েছে।');
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

    public function updateMember(Request $request, $id)
    {
        $member = SupportMember::findOrFail($id);
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

        $avatarUrl = $member->avatar_url;
        if ($request->hasFile('avatar')) {
            if ($member->avatar_url) {
                $oldPath = str_replace(asset(''), public_path(''), $member->avatar_url);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            $image = $request->file('avatar');
            $name = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('/uploads/support');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            $image->move($destinationPath, $name);
            $avatarUrl = asset('uploads/support/' . $name);
        }

        $member->update([
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

        return back()->with('success', 'Support member updated successfully.');
    }

    public function updateService(Request $request, $id)
    {
        $service = SupportService::findOrFail($id);
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'link' => 'nullable|url',
            'button_text' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $iconUrl = $service->icon_url;
        if ($request->hasFile('icon')) {
            if ($service->icon_url) {
                $oldPath = str_replace(asset(''), public_path(''), $service->icon_url);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            $image = $request->file('icon');
            $name = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('/uploads/support');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            $image->move($destinationPath, $name);
            $iconUrl = asset('uploads/support/' . $name);
        }

        $service->update([
            'name' => $request->name,
            'description' => $request->description,
            'icon_url' => $iconUrl,
            'link' => $request->link,
            'button_text' => $request->button_text ?? 'WhatsApp সাপোর্ট',
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return back()->with('success', 'Support service updated successfully.');
    }
}
