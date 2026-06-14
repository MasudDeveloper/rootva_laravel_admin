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

            // Distribute Commission if status changes to confirmed
            if ($newStatus == 'confirmed' && $oldStatus != 'confirmed') {
                $this->giveSimOfferCommission($simRequest->user, $simRequest->price, $simRequest->id);
            }

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
            'offer_type'    => $request->offer_type ?? 'drive',
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
            'offer_type'     => 'nullable',
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
                'offer_type'    => $request->offer_type ?? 'drive',
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
            'offer_type'    => $request->offer_type ?? 'drive',
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

    private function giveSimOfferCommission($user, $amount, $tranId)
    {
        if (!$user) return;

        $now = now()->toDateTimeString();
        $currentTime = now()->format("d-m-Y h:i A");

        // 1. Self Commission (1.5%)
        $selfComm = round($amount * 0.015, 2);
        if ($selfComm > 0) {
            $user->increment('wallet_balance', $selfComm);
            Transaction::create([
                'user_id' => $user->id,
                'refer_id' => $user->referCode,
                'amount' => $selfComm,
                'type' => 'commission',
                'payment_gateway' => 'SIM Offer Commission',
                'description' => "SIM Offer Commission for Request ID: {$tranId}",
                'update_at' => $currentTime,
                'created_at' => $now,
                'date' => $now
            ]);
        }

        // 2. Upline Commission (2 levels, 0.05% each)
        $uplineComm = round($amount * 0.0005, 2);
        if ($uplineComm <= 0) return;

        $currentUplineReferCode = $user->referredBy;
        for ($i = 1; $i <= 2; $i++) {
            if (empty($currentUplineReferCode) || $currentUplineReferCode == "0") break;

            $upline = \App\Models\SignUp::where('referCode', $currentUplineReferCode)->first();
            if (!$upline) break;

            $upline->increment('wallet_balance', $uplineComm);
            Transaction::create([
                'user_id' => $upline->id,
                'refer_id' => $user->referCode,
                'amount' => $uplineComm,
                'type' => 'commission',
                'payment_gateway' => 'SIM Offer Team Commission',
                'description' => "SIM Offer Commission from {$user->name} (Level {$i})",
                'update_at' => $currentTime,
                'created_at' => $now,
                'date' => $now
            ]);

            $currentUplineReferCode = $upline->referredBy;
        }
    }
}
