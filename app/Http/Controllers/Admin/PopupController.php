<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PopupBanner;
use Illuminate\Support\Facades\File;

class PopupController extends Controller
{
    public function index()
    {
        $popups = PopupBanner::orderBy('id', 'desc')->get();
        return view('admin.popups.index', compact('popups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'message' => 'required|string',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('uploads/popups'), $imageName);
            $imageUrl = url('uploads/popups/' . $imageName);

            PopupBanner::create([
                'image_url' => $imageUrl,
                'message' => $request->message,
                'button_text' => $request->button_text ?? '',
                'button_url' => $request->button_url ?? '',
            ]);

            return back()->with('success', 'Popup banner uploaded successfully!');
        }

        return back()->with('error', 'Failed to upload image.');
    }

    public function destroy($id)
    {
        $popup = PopupBanner::findOrFail($id);
        
        // Delete file from storage
        $path = str_replace(url('/'), public_path(), $popup->image_url);
        if (File::exists($path)) {
            File::delete($path);
        }

        $popup->delete();

        return back()->with('success', 'Popup banner deleted successfully!');
    }
}
