<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\File;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('id', 'desc')->paginate(25);
        return view('admin.services.index', compact('services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'resell_price' => 'nullable|numeric',
            'reselling_price' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
            'image_url' => 'nullable|string',
        ]);

        $resellPrice = $request->filled('resell_price') ? $request->resell_price : ($request->reselling_price ?? 0);

        $imageUrl = $request->image_url;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('uploads/services');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            $image->move($destinationPath, $filename);
            $imageUrl = asset('uploads/services/' . $filename);
        }

        Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'image_url' => $imageUrl,
            'price' => $request->price,
            'resell_price' => $resellPrice,
            'buylink' => $request->buylink,
            'created_at' => now()->toDateTimeString(),
        ]);

        return back()->with('success', 'অনলাইন সেবা সফলভাবে তৈরি করা হয়েছে!');
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'resell_price' => 'nullable|numeric',
            'reselling_price' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
            'image_url' => 'nullable|string',
        ]);

        $resellPrice = $request->filled('resell_price') ? $request->resell_price : ($request->filled('reselling_price') ? $request->reselling_price : $service->resell_price);

        $imageUrl = $request->filled('image_url') ? $request->image_url : $service->image_url;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
            $destinationPath = public_path('uploads/services');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            $image->move($destinationPath, $filename);
            $imageUrl = asset('uploads/services/' . $filename);
        }

        $service->update([
            'name' => $request->name,
            'description' => $request->description,
            'image_url' => $imageUrl,
            'price' => $request->price,
            'resell_price' => $resellPrice,
            'buylink' => $request->buylink,
        ]);

        return back()->with('success', 'অনলাইন সেবা সফলভাবে আপডেট করা হয়েছে!');
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();
        return back()->with('success', 'সার্ভিস সফলভাবে ডিলিট করা হয়েছে!');
    }
}

