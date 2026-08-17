<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\BottomBanner;
use Illuminate\Support\Facades\File;

class BottomBannerController extends Controller
{
    public function index()
    {
        $banners = BottomBanner::orderBy('id', 'desc')->get();
        return view('admin.bottom_banners.index', compact('banners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'redirect_url' => 'nullable|url'
        ]);

        if ($request->hasFile('banner')) {
            $image = $request->file('banner');
            $name = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('/uploads/bottom_banners');
            
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            
            $image->move($destinationPath, $name);
            $url = asset('uploads/bottom_banners/' . $name);

            BottomBanner::create([
                'image_url' => $url,
                'redirect_url' => $request->redirect_url
            ]);
        }

        return back()->with('success', 'Bottom Banner uploaded successfully.');
    }

    public function destroy($id)
    {
        $banner = BottomBanner::findOrFail($id);
        
        $path = str_replace(asset(''), public_path(''), $banner->image_url);
        if (File::exists($path)) {
            File::delete($path);
        }

        $banner->delete();
        return back()->with('success', 'Bottom Banner deleted successfully.');
    }
}
