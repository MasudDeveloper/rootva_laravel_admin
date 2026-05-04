<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banner;
use App\Models\Review;
use App\Models\Category;
use App\Models\Tutorial;
use App\Models\SimOffer;
use App\Models\SignUp;
use Illuminate\Support\Facades\DB;

class LegacyContentController extends Controller
{
    /**
     * Legacy Banners (get_banners.php)
     */
    public function getBanners()
    {
        $banners = Banner::all();
        return response()->json($banners);
    }

    /**
     * Legacy Reviews (get_reviews.php)
     */
    public function getReviews()
    {
        $reviews = Review::all();
        return response()->json($reviews);
    }

    /**
     * Legacy Social Links (get_social_links.php)
     */
    public function getSocialLinks()
    {
        $links = DB::table('social_links')->first();
        return response()->json(['social_links' => $links]);
    }

    /**
     * Legacy Categories (get_categories.php)
     */
    public function getCategories()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    /**
     * Legacy Popup Data (get_popup.php)
     */
    public function getPopupData()
    {
        $popup = DB::table('popups')->latest()->first();
        return response()->json($popup);
    }

    /**
     * Legacy Tutorials (get_tutorials.php)
     */
    public function getTutorials()
    {
        $tutorials = Tutorial::all();
        return response()->json($tutorials);
    }

}
