<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use Illuminate\Support\Facades\File;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::orderBy('id', 'desc')->get();
        return view('admin.reviews.index', compact('reviews'));
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
            $destinationPath = public_path('/uploads/reviews');
            
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            
            $image->move($destinationPath, $name);
            $url = asset('uploads/reviews/' . $name);

            Review::create([
                'image_url' => $url,
                'redirect_url' => $request->redirect_url
            ]);
        }

        return back()->with('success', 'Review banner uploaded successfully.');
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        
        $path = str_replace(asset(''), public_path(''), $review->image_url);
        if (File::exists($path)) {
            File::delete($path);
        }

        $review->delete();
        return back()->with('success', 'Review banner deleted successfully.');
    }
}
