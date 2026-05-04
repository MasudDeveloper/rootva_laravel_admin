<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SimOffer;
use App\Models\SimOfferManage;
use App\Models\SimOfferRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class SimOfferController extends Controller
{
    public function index()
    {
        $offers = SimOffer::orderBy('id', 'desc')->paginate(25, ['*'], 'offers_page');
        $requests = SimOfferRequest::with(['offer', 'user'])->orderBy('id', 'desc')->paginate(25, ['*'], 'requests_page');
        $settings = SimOfferManage::first();
        return view('admin.sim_offers.index', compact('offers', 'requests', 'settings'));
    }

    public function updateRequestStatus(Request $request, $id)
    {
        $simRequest = SimOfferRequest::with('user')->findOrFail($id);
        $oldStatus = $simRequest->status;
        $newStatus = $request->input('status');
        
        DB::beginTransaction();
        try {
            $simRequest->status = $newStatus;
            
            if ($newStatus == 'rejected') {
                $simRequest->reject_reason = $request->input('reject_reason');
                
                // Refund money if it was previously pending or confirmed (and now being rejected)
                // In our current flow, money is deducted at submission.
                if ($oldStatus != 'rejected') {
                    $user = $simRequest->user;
                    if ($user) {
                        $user->increment('voucher_balance', $simRequest->price);
                        
                        // Create a transaction log for refund
                        Transaction::create([
                            'user_id' => $user->id,
                            'refer_id' => $user->referCode,
                            'amount' => $simRequest->price,
                            'type' => 'income',
                            'payment_gateway' => 'Voucher',
                            'description' => 'SIM Offer Refunded (ID: '.$simRequest->id.')',
                            'update_at' => date("d-m-Y h:i A"),
                            'created_at' => date("d-m-Y h:i A"),
                            'date' => now()
                        ]);
                    }
                }
            }
            
            $simRequest->save();
            DB::commit();
            return back()->with('success', 'Request updated successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
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

    public function update(Request $request, $id)
    {
        $request->validate([
            'operator_name' => 'required',
            'title'         => 'required',
            'regular_price' => 'required|numeric',
            'offer_price'   => 'required|numeric',
        ]);

        $offer = SimOffer::findOrFail($id);
        $offer->update([
            'operator_name' => $request->operator_name,
            'title'         => $request->title,
            'offer_details' => $request->offer_details,
            'regular_price' => $request->regular_price,
            'offer_price'   => $request->offer_price,
        ]);

        return back()->with('success', 'SIM Offer updated successfully!');
    }

    public function destroy($id)
    {
        SimOffer::findOrFail($id)->delete();
        return back()->with('success', 'SIM Offer deleted successfully!');
    }

    public function updateSettings(Request $request)
    {
        $settings = SimOfferManage::first();
        if (!$settings) {
            $settings = new SimOfferManage();
        }

        $settings->status = $request->has('status') ? 1 : 0;
        $settings->notice_text = $request->notice_text;
        $settings->save();

        return back()->with('success', 'SIM Offer settings updated successfully!');
    }
}
