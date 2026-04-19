<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;

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
            'resell_price' => 'required|numeric',
        ]);

        Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'image_url' => $request->image_url,
            'price' => $request->price,
            'resell_price' => $request->resell_price,
            'buylink' => $request->buylink,
            'created_at' => now()->toDateTimeString(),
        ]);

        return back()->with('success', 'Service added successfully!');
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'resell_price' => 'required|numeric',
        ]);

        $service->update([
            'name' => $request->name,
            'description' => $request->description,
            'image_url' => $request->image_url,
            'price' => $request->price,
            'resell_price' => $request->resell_price,
            'buylink' => $request->buylink,
        ]);

        return back()->with('success', 'Service updated successfully!');
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();
        return back()->with('success', 'Service deleted successfully!');
    }
}
