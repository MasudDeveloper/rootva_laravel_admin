<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialLink;
use App\Models\PaymentNumber;
use App\Models\AppUpdate;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Display all app settings.
     */
    public function index()
    {
        $social = SocialLink::first() ?? new SocialLink();
        $payments = PaymentNumber::first() ?? new PaymentNumber();
        $updates = AppUpdate::orderBy('id', 'desc')->get();
        
        return view('admin.settings.index', compact('social', 'payments', 'updates'));
    }

    /**
     * Unified update method for all settings.
     */
    public function update(Request $request)
    {
        DB::transaction(function () use ($request) {
            // 1. Update Social Links
            $social = SocialLink::first();
            if (!$social) {
                $social = new SocialLink();
                $social->created_at = now();
            }
            // Filter request to only include social_links columns
            $socialFields = [
                'facebook_group', 'whatsapp_group', 'whatsapp_business_group', 'telegram_group', 
                'telegram_reselling_group', 'telegram_sim_offer_group', 'telegram_bot', 
                'youtube_channel', 'support_number', 'support_reselling', 'support_password', 
                'support_facebook', 'support_verify', 'instagram_work_submit', 'instagram_work_telegram', 
                'instagram_work_massenger', 'email_work_submit', 'email_work_telegram', 
                'email_work_massenger', 'facebook_work_submit', 'facebook_work_telegram', 
                'facebook_work_massenger', 'tiktok_work_submit', 'tiktok_work_telegram', 
                'tiktok_work_massenger', 'customer_meeting', 'business_meeting'
            ];
            $social->fill($request->only($socialFields));
            $social->save();

            // 2. Update Payment Numbers
            $payments = PaymentNumber::first();
            if (!$payments) {
                $payments = new PaymentNumber();
            }
            $paymentFields = ['bkash', 'nagad', 'rocket', 'upay', 'verify_amount'];
            if ($request->hasAny($paymentFields)) {
                $payments->fill($request->only($paymentFields));
                $payments->save();
            }

            // 3. Add App Update Entry
            if ($request->filled('version_code')) {
                AppUpdate::create([
                    'version_code' => $request->version_code,
                    'update_link' => $request->update_link,
                    'update_message' => $request->update_message,
                    'created_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Global app settings updated successfully!');
    }
}
