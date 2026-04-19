<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Banner;
use Illuminate\Support\Facades\File;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('id', 'desc')->get();
        return view('admin.banners.index', compact('banners'));
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
            $destinationPath = public_path('/uploads/banners');
            
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            
            $image->move($destinationPath, $name);
            $url = asset('uploads/banners/' . $name);

            Banner::create([
                'image_url' => $url,
                'redirect_url' => $request->redirect_url
            ]);
        }

        return back()->with('success', 'Banner uploaded successfully.');
    }

    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);
        
        // Delete image file (optional, but good practice)
        $path = str_replace(asset(''), public_path(''), $banner->image_url);
        if (File::exists($path)) {
            File::delete($path);
        }

        $banner->delete();
        return back()->with('success', 'Banner deleted successfully.');
    }
}
