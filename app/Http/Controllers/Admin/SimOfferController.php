<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SimOffer;

class SimOfferController extends Controller
{
    public function index()
    {
        $offers = SimOffer::orderBy('id', 'desc')->paginate(25);
        return view('admin.sim_offers.index', compact('offers'));
    }

    /**
     * Save a single new offer (from the Add/Copy modal)
     */
    public function store(Request $request)
    {
        $request->validate([
            'operator_name' => 'required',
            'title'         => 'required',
            'regular_price' => 'required|numeric',
            'offer_price'   => 'required|numeric',
        ]);

        SimOffer::create([
            'operator_name' => $request->operator_name,
            'title'         => $request->title,
            'offer_details' => $request->offer_details,
            'regular_price' => $request->regular_price,
            'offer_price'   => $request->offer_price,
            'created_at'    => now()->toDateTimeString(),
        ]);

        return back()->with('success', 'SIM Offer added successfully!');
    }

    /**
     * Bulk save parsed offers (from the Paste & Parse tab)
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'operator_name'  => 'required',
            'title'          => 'required|array|min:1',
            'offer_details'  => 'required|array',
            'regular_price'  => 'required|array',
            'offer_price'    => 'required|array',
        ]);

        $operator  = $request->operator_name;
        $titles    = $request->title;
        $details   = $request->offer_details;
        $regulars  = $request->regular_price;
        $prices    = $request->offer_price;
        $count     = count($titles);
        $saved     = 0;

        for ($i = 0; $i < $count; $i++) {
            if (empty($titles[$i]) || empty($regulars[$i]) || empty($prices[$i])) continue;

            SimOffer::create([
                'operator_name' => $operator,
                'title'         => $titles[$i],
                'offer_details' => $details[$i] ?? '',
                'regular_price' => (float) $regulars[$i],
                'offer_price'   => (float) $prices[$i],
                'created_at'    => now()->toDateTimeString(),
            ]);
            $saved++;
        }

        return redirect()->route('admin.sim-offers.index')
            ->with('success', "{$saved} টি অফার সফলভাবে save হয়েছে!");
    }

    public function destroy($id)
    {
        SimOffer::findOrFail($id)->delete();
        return back()->with('success', 'SIM Offer deleted successfully!');
    }
}
