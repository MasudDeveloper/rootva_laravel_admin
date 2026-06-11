<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SimOffer;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;

class LegacySimOfferController extends Controller
{
    /**
     * Legacy SIM Offers (get_sim_offer.php)
     * Optimized with selective columns and faster mapping.
     */
    public function getSimOffers()
    {
        $offers = SimOffer::select('id', 'title', 'offer_details', 'operator_name', 'regular_price', 'offer_price', 'offer_type', 'created_at')
            ->get()
            ->map(function($offer) {
                return [
                    'id'            => (int)$offer->id,
                    'title'         => $offer->title,
                    'offer_details' => $offer->offer_details,
                    'operator_name' => $offer->operator_name,
                    'regular_price' => (double)$offer->regular_price,
                    'offer_price'   => (double)$offer->offer_price,
                    'offer_type'    => $offer->offer_type ?? 'drive',
                    'created_at'    => $offer->created_at,
                ];
            });
        
        return response()->json($offers);
    }

    /**
     * SIM Offer Management (sim_offer_manage_api.php)
     */
    public function getSimOfferManage()
    {
        $settings = DB::table('sim_offer_manage')->select('status', 'notice_text')->first();
        
        return response()->json([
            'success'       => true,
            'status_on_off' => $settings ? (int)$settings->status : 1,
            'notice_text'   => $settings ? (string)$settings->notice_text : 'No notice available'
        ]);
    }

    /**
     * User Offer History (get_sim_offer_history.php)
     */
    public function getUserOfferHistory(Request $request)
    {
        $user_id = $request->query('user_id');
        
        if (empty($user_id)) {
            return response()->json(["success" => false, "message" => "User ID missing"]);
        }

        $history = DB::table('sim_offer_requests as req')
            ->leftJoin('sim_offers as off', 'req.offer_id', '=', 'off.id')
            ->where('req.user_id', $user_id)
            ->select('req.id', 'req.phone_number', 'req.price', 'req.status', 'req.reject_reason', 'req.created_at', 
                     'off.title', 'off.offer_details', 'off.operator_name', 'off.offer_price')
            ->orderBy('req.id', 'desc')
            ->get()
            ->map(function($req) {
                return [
                    'request_id'    => (int)$req->id,
                    'phone_number'  => $req->phone_number,
                    'price'         => (string)$req->price,
                    'status'        => $req->status,
                    'reject_reason' => $req->reject_reason,
                    'created_at'    => $req->created_at,
                    'title'         => $req->title ?? 'N/A',
                    'offer_details' => $req->offer_details ?? 'N/A',
                    'operator_name' => $req->operator_name ?? 'N/A',
                    'offer_price'   => (double)($req->offer_price ?? 0),
                ];
            });
        
        return response()->json($history);
    }

    /**
     * Submit SIM Offer Request (submit_sim_offer_request.php)
     */
    public function submitSimOfferRequest(Request $request)
    {
        $userId = $request->input('user_id');
        $offerId = $request->input('offer_id');
        $phone = $request->input('phone_number');
        $price = (double)$request->input('price');

        $user = SignUp::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'ইউজার পাওয়া যায়নি']);
        }

        if ($user->voucher_balance < $price) {
            return response()->json(['success' => false, 'message' => 'পর্যাপ্ত ব্যালেন্স নেই']);
        }

        DB::beginTransaction();
        try {
            // ব্যালেন্স কমানো
            $user->decrement('voucher_balance', $price);

            // ট্রানজেকশন রেকর্ড যোগ করা
            DB::table('transactions')->insert([
                'user_id' => $userId,
                'refer_id' => $user->referCode,
                'amount' => $price,
                'type' => 'voucher_payment',
                'payment_gateway' => 'Voucher',
                'description' => 'SIM Offer Request (Offer ID: '.$offerId.')',
                'update_at' => date("d-m-Y h:i A"),
                'created_at' => date("d-m-Y h:i A"),
                'date' => now()
            ]);

            // রিকোয়েস্ট সেভ করা
            DB::table('sim_offer_requests')->insert([
                'user_id' => $userId,
                'offer_id' => $offerId,
                'phone_number' => $phone,
                'price' => $price,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'রিকোয়েস্টটি সফলভাবে পাঠানো হয়েছে']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'রিকোয়েস্ট পাঠাতে ব্যর্থ হয়েছে: ' . $e->getMessage()]);
        }
    }

    /**
     * Confirm SIM Offer (confirm_sim_offer.php)
     */
    public function confirmSimOffer(Request $request)
    {
        $id = $request->input('request_id');
        
        $simRequest = \App\Models\SimOfferRequest::with('user')->find($id);
        if ($simRequest && $simRequest->status !== 'confirmed') {
            $simRequest->status = 'confirmed';
            $simRequest->save();

            // Give Commission
            $this->giveSimOfferCommission($simRequest->user, $simRequest->price, $simRequest->id);

            return response()->json([
                "error" => false,
                "success" => true,
                "message" => "Offer confirmed"
            ]);
        }
        
        return response()->json([
            "error" => true,
            "success" => false,
            "message" => "Failed to update or already confirmed"
        ]);
    }

    private function giveSimOfferCommission($user, $amount, $tranId)
    {
        if (!$user) return;

        $now = now()->toDateTimeString();
        $currentTime = now()->format("d-m-Y h:i A");

        // 1. Self Commission (1.5%)
        $selfComm = round($amount * 0.015, 2);
        if ($selfComm > 0) {
            DB::table('sign_up')->where('id', $user->id)->increment('wallet_balance', $selfComm);
            DB::table('transactions')->insert([
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

            $upline = DB::table('sign_up')->where('referCode', $currentUplineReferCode)->first();
            if (!$upline) break;

            DB::table('sign_up')->where('id', $upline->id)->increment('wallet_balance', $uplineComm);
            DB::table('transactions')->insert([
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
