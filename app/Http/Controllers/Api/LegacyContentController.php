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
}
