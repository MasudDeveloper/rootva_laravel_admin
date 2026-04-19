<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\SimOffer;
use App\Models\Product;
use App\Models\Course;
use App\Models\Service;
use App\Models\SignUp;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * Get data for Home Screen
     */
    public function getHomeData()
    {
        return response()->json([
            'banners' => Banner::all(),
            'featured_sim_offers' => SimOffer::latest()->take(6)->get(),
            'featured_products' => Product::latest()->take(6)->get(),
        ]);
    }

    /**
     * Get SIM Offers
     */
    public function getSimOffers(Request $request)
    {
        $operator = $request->query('operator');
        $query = SimOffer::query();
        
        if ($operator) {
            $query->where('operator_name', $operator);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Get Products
     */
    public function getProducts(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $query = Product::with('category');

        if ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return response()->json($query->orderBy('id', 'desc')->get());
    }

    /**
     * Get Courses
     */
    public function getCourses()
    {
        return response()->json(Course::all());
    }

    /**
     * Get Services
     */
    public function getServices()
    {
        return response()->json(Service::all());
    }

    /**
     * Get Profile / User Details
     */
    public function getProfile(Request $request)
    {
        $api_token = $request->header('Authorization') ?: $request->query('api_token');
        
        // Remove 'Bearer ' prefix if present
        if (strpos($api_token, 'Bearer ') === 0) {
            $api_token = substr($api_token, 7);
        }

        if (!$api_token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = SignUp::where('api_token', $api_token)->first();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($user);
    }
}
