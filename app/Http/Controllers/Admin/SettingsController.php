<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SocialLink;
use App\Models\PaymentNumber;
use App\Models\AppUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
        
        $maintenance = ['is_maintenance' => 0, 'maintenance_message' => '', 'maintenance_countdown' => ''];
        $path = storage_path('app/maintenance.json');
        if (file_exists($path)) {
            $maintenance = json_decode(file_get_contents($path), true);
        }
        
        return view('admin.settings.index', compact('social', 'payments', 'updates', 'maintenance'));
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

            // 4. Update Maintenance Settings
            if ($request->has('is_maintenance')) {
                $countdown = $request->maintenance_countdown ?? '';
                if (!empty($countdown)) {
                    $countdown = date('Y-m-d H:i:s', strtotime($countdown));
                }
                $maintenanceData = [
                    'is_maintenance' => (int)$request->is_maintenance,
                    'maintenance_message' => $request->maintenance_message ?? '',
                    'maintenance_countdown' => $countdown
                ];
                file_put_contents(storage_path('app/maintenance.json'), json_encode($maintenanceData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            // Clear all API caches so users get the fresh settings instantly
            Cache::forget('api_social_links');
            Cache::forget('api_app_update');
            Cache::forget('api_popup_banner');
            Cache::forget('api_banners');
            Cache::forget('api_reviews');
        });

        return back()->with('success', 'Global app settings updated successfully!');
    }
}
