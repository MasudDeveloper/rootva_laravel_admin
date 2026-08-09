<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\SignUp;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::with('user')->orderBy('id', 'desc')->paginate(25);
        return view('admin.vendors.index', compact('vendors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|unique:vendors,user_id|exists:sign_up,id',
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:pending,approved,suspended'
        ]);

        Vendor::create([
            'user_id' => $request->user_id,
            'store_name' => $request->store_name,
            'store_description' => $request->store_description,
            'commission_rate' => $request->commission_rate,
            'status' => $request->status,
        ]);

        return back()->with('success', 'Vendor registered successfully!');
    }

    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $request->validate([
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:pending,approved,suspended'
        ]);

        $vendor->update([
            'store_name' => $request->store_name,
            'store_description' => $request->store_description,
            'commission_rate' => $request->commission_rate,
            'status' => $request->status,
        ]);

        return back()->with('success', 'Vendor updated successfully!');
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();
        return back()->with('success', 'Vendor deleted successfully!');
    }
}
