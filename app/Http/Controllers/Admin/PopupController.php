<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PopupBanner;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

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
            $imageUrl = '/uploads/popups/' . $imageName;

            PopupBanner::create([
                'image_url' => $imageUrl,
                'message' => $request->message,
                'button_text' => $request->button_text ?? '',
                'button_url' => $request->button_url ?? '',
            ]);

            Cache::forget('api_popup_banner');

            return back()->with('success', 'Popup banner uploaded successfully!');
        }

        return back()->with('error', 'Failed to upload image.');
    }

    public function setActive($id)
    {
        $popup = PopupBanner::findOrFail($id);

        $latestPopup = PopupBanner::orderBy('id', 'desc')->first();
        if ($latestPopup && $latestPopup->id == $popup->id) {
            return back()->with('info', 'এই পপআপটি ইতোমধ্যে অ্যাপে সক্রিয় আছে।');
        }

        $rawImageUrl = $popup->getRawOriginal('image_url') ?? $popup->image_url;
        $message = $popup->message;
        $buttonText = $popup->button_text;
        $buttonUrl = $popup->button_url;

        // Delete old row and re-create to give it highest ID (making it active in the app)
        $popup->delete();

        PopupBanner::create([
            'image_url' => $rawImageUrl,
            'message' => $message,
            'button_text' => $buttonText,
            'button_url' => $buttonUrl,
        ]);

        Cache::forget('api_popup_banner');

        return back()->with('success', 'পপআপটি সফলভাবে অ্যাপে সক্রিয় করা হয়েছে!');
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

        Cache::forget('api_popup_banner');

        return back()->with('success', 'Popup banner deleted successfully!');
    }
}
