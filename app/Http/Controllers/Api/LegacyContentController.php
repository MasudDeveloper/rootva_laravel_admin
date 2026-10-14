<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\BottomBanner;
use App\Models\Review;
use App\Models\ProductCategory;
use App\Models\SupportMember;
use App\Models\SupportService;

use App\Models\SimOffer;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LegacyContentController extends Controller
{
    /**
     * Legacy Banners (get_banners.php)
     */
    public function getBanners()
    {
        $banners = Cache::remember('api_banners', 1800, function () {
            return Banner::all();
        });
        return response()->json($banners);
    }

    /**
     * Legacy Bottom Banners (get_bottom_banners.php)
     */
    public function getBottomBanners()
    {
        $banners = Cache::remember('api_bottom_banners', 1800, function () {
            return BottomBanner::all();
        });
        return response()->json($banners);
    }

    /**
     * Legacy Reviews (get_reviews.php)
     */
    public function getReviews()
    {
        $reviews = Cache::remember('api_reviews', 1800, function () {
            return Review::all();
        });
        return response()->json($reviews);
    }

    /**
     * Legacy Social Links (get_social_links.php)
     */
    public function getSocialLinks()
    {
        $links = Cache::remember('api_social_links', 1800, function () {
            return DB::table('social_links')->first();
        });
        return response()->json(['social_links' => $links]);
    }

    /**
     * Legacy Categories (get_categories.php)
     */
    public function getCategories()
    {
        $categories = Cache::remember('api_categories', 1800, function () {
            return ProductCategory::all();
        });
        return response()->json($categories);
    }

    /**
     * Legacy Popup Data (get_popup.php)
     */
    public function getPopupData()
    {
        $popup = Cache::remember('api_popup_banner', 900, function () {
            return DB::table('popup_banner')->latest('id')->first();
        });
        if ($popup) {
            return response()->json([
                'success' => true,
                'image_url' => $popup->image_url,
                'message' => $popup->message,
                'button_text' => $popup->button_text,
                'button_url' => $popup->button_url
            ]);
        }
        return response()->json(['success' => false]);
    }

    /**
     * Get Support Center Data (get_support_center.php)
     */
    public function getSupportCenter()
    {
        $members = SupportMember::orderBy('sort_order', 'asc')->get();
        $services = SupportService::orderBy('sort_order', 'asc')->get();
        return response()->json([
            'members' => $members,
            'services' => $services
        ]);
    }

    /**
     * Report App Crash (report_app_crash.php)
     */
    public function reportAppCrash(Request $request)
    {
        try {
            $userId = $request->input('user_id');
            $userPhone = $request->input('user_phone');

            // If userId provided and no phone, attempt to fetch phone
            if ($userId && !$userPhone) {
                $user = DB::table('sign_up')->where('id', $userId)->first(['number']);
                if ($user) {
                    $userPhone = $user->number;
                }
            }

            $exceptionMessage = $request->input('exception_message');
            $stackTrace = $request->input('stack_trace');

            // Extract location if not explicitly provided
            $crashLocation = $request->input('crash_location');
            if (empty($crashLocation) && !empty($stackTrace)) {
                // Find first occurrence of com.mrdeveloper.rootva in stack trace
                if (preg_match('/at\s+(com\.mrdeveloper\.rootva\.[a-zA-Z0-9_$.]+\([a-zA-Z0-9_]+\.java:\d+\))/', $stackTrace, $matches)) {
                    $crashLocation = $matches[1];
                }
            }

            \App\Models\AppCrashReport::create([
                'user_id' => $userId ?: null,
                'user_phone' => $userPhone ?: null,
                'app_version' => $request->input('app_version'),
                'device_model' => $request->input('device_model'),
                'android_version' => $request->input('android_version'),
                'exception_class' => $request->input('exception_class'),
                'exception_message' => $exceptionMessage,
                'crash_location' => $crashLocation,
                'stack_trace' => $stackTrace,
                'status' => 'Unresolved',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Crash report logged successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
