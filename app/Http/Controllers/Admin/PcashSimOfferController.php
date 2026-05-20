<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PcashSimOffer;

class PcashSimOfferController extends Controller
{
    public function index()
    {
        $offers = PcashSimOffer::latest()->paginate(20);
        return view('admin.pcash.sim_offers.index', compact('offers'));
    }

    public function create()
    {
        return view('admin.pcash.sim_offers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'operator' => 'required',
            'price' => 'required|numeric',
            'type' => 'required|integer',
            'status' => 'required|boolean'
        ]);

        PcashSimOffer::create($request->all());
        return redirect()->route('admin.pcash.sim_offers.index')->with('success', 'Offer created successfully.');
    }

    public function edit(string $id)
    {
        $offer = PcashSimOffer::findOrFail($id);
        return view('admin.pcash.sim_offers.edit', compact('offer'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required',
            'operator' => 'required',
            'price' => 'required|numeric',
            'type' => 'required|integer',
            'status' => 'required|boolean'
        ]);

        $offer = PcashSimOffer::findOrFail($id);
        $offer->update($request->all());
        return redirect()->route('admin.pcash.sim_offers.index')->with('success', 'Offer updated successfully.');
    }

    public function destroy(string $id)
    {
        $offer = PcashSimOffer::findOrFail($id);
        $offer->delete();
        return redirect()->back()->with('success', 'Offer deleted successfully.');
    }
}
