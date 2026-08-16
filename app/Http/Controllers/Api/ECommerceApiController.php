<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SignUp;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\ProductFavorite;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class ECommerceApiController extends Controller
{
    /**
     * Apply for Vendor Status
     */
    public function applyVendor(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'document' => 'nullable|image|max:2048'
        ]);

        $user_id = $request->input('user_id');
        
        $existingVendor = Vendor::where('user_id', $user_id)->first();
        if ($existingVendor) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied or are registered as a vendor. Current status: ' . $existingVendor->status,
                'status' => $existingVendor->status
            ]);
        }

        $destinationPath = public_path('/uploads/vendors');
        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true, true);
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoName = time() . '_logo_' . uniqid() . '_' . $logo->getClientOriginalName();
            $logo->move($destinationPath, $logoName);
            $logoPath = '/uploads/vendors/' . $logoName;
        }

        $docPath = null;
        if ($request->hasFile('document')) {
            $doc = $request->file('document');
            $docName = time() . '_doc_' . uniqid() . '_' . $doc->getClientOriginalName();
            $doc->move($destinationPath, $docName);
            $docPath = '/uploads/vendors/' . $docName;
        }

        $vendor = Vendor::create([
            'user_id' => $user_id,
            'store_name' => $request->store_name,
            'store_description' => $request->store_description,
            'logo' => $logoPath,
            'document' => $docPath,
            'status' => 'pending',
            'commission_rate' => 10.00
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendor application submitted successfully!',
            'status' => 'pending'
        ]);
    }

    /**
     * Get Vendor Status
     */
    public function getVendorStatus(Request $request)
    {
        $user_id = $request->input('user_id');
        $vendor = Vendor::where('user_id', $user_id)->first();

        if (!$vendor) {
            return response()->json([
                'success' => true,
                'is_vendor' => false,
                'status' => 'none'
            ]);
        }

        return response()->json([
            'success' => true,
            'is_vendor' => ($vendor->status === 'approved'),
            'status' => $vendor->status,
            'store_name' => $vendor->store_name,
            'store_description' => $vendor->store_description,
            'logo' => $vendor->logo,
            'document' => $vendor->document
        ]);
    }

    /**
     * Get Vendor Dashboard Statistics
     */
    public function getVendorDashboard(Request $request)
    {
        $user_id = $request->input('user_id');
        $vendor = Vendor::where('user_id', $user_id)->first();

        if (!$vendor || $vendor->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or vendor account not approved yet.'
            ], 403);
        }

        $totalProducts = Product::where('vendor_id', $vendor->id)->count();
        $approvedProducts = Product::where('vendor_id', $vendor->id)->where('is_approved', 1)->count();
        $pendingProducts = Product::where('vendor_id', $vendor->id)->where('is_approved', 0)->count();

        // Calculate sales stats if any orders exist (using DB order checks)
        // For now, let's return clean mocks or count of orders referencing vendor products
        $salesCount = DB::table('orders')
            ->whereIn('product_id', function($query) use ($vendor) {
                $query->select('id')->from('products')->where('vendor_id', $vendor->id);
            })->count();

        $earnings = DB::table('orders')
            ->whereIn('product_id', function($query) use ($vendor) {
                $query->select('id')->from('products')->where('vendor_id', $vendor->id);
            })
            ->where('status', 'completed')
            ->sum('price');

        return response()->json([
            'success' => true,
            'store_name' => $vendor->store_name,
            'total_products' => $totalProducts,
            'approved_products' => $approvedProducts,
            'pending_products' => $pendingProducts,
            'sales_count' => $salesCount,
            'earnings' => (double)$earnings,
            'commission_rate' => $vendor->commission_rate
        ]);
    }

    /**
     * Vendor Product Upload
     */
    public function uploadProduct(Request $request)
    {
        $user_id = $request->input('user_id');
        $vendor = Vendor::where('user_id', $user_id)->where('status', 'approved')->first();

        if (!$vendor) {
            return response()->json([
                'success' => false,
                'message' => 'Only approved vendors can upload products.'
            ], 403);
        }

        $request->validate([
            'category_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'reselling_price' => 'required|numeric',
            'description' => 'nullable|string',
            'images' => 'required|array',
            'images.*' => 'image|max:2048'
        ]);

        $destinationPath = public_path('/uploads/products');
        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true, true);
        }

        $uploadedImages = [];
        foreach ($request->file('images') as $image) {
            $name = time() . '_' . uniqid() . '_' . $image->getClientOriginalName();
            $image->move($destinationPath, $name);
            $uploadedImages[] = '/uploads/products/' . $name;
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'vendor_id' => $vendor->id,
            'name' => $request->name,
            'price' => $request->price,
            'reselling_price' => $request->reselling_price,
            'description' => $request->description,
            'image' => count($uploadedImages) > 0 ? $uploadedImages[0] : null,
            'images' => $uploadedImages,
            'is_approved' => 0, // Needs admin moderation
            'stock' => $request->input('stock', 10),
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product submitted successfully and is pending admin approval!',
            'product' => $product
        ]);
    }

    /**
     * Toggle Favorite
     */
    public function toggleFavorite(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer'
        ]);

        $user_id = $request->input('user_id');
        $product_id = $request->product_id;

        $favorite = ProductFavorite::where('user_id', $user_id)
            ->where('product_id', $product_id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json([
                'success' => true,
                'is_favorite' => false,
                'message' => 'Removed from favorites'
            ]);
        }

        ProductFavorite::create([
            'user_id' => $user_id,
            'product_id' => $product_id
        ]);

        return response()->json([
            'success' => true,
            'is_favorite' => true,
            'message' => 'Added to favorites'
        ]);
    }

    /**
     * Get Favorite Products
     */
    public function getFavorites(Request $request)
    {
        $user_id = $request->input('user_id');
        
        $favorites = ProductFavorite::where('user_id', $user_id)
            ->with(['product' => function($q) {
                $q->approved();
            }])
            ->get()
            ->pluck('product')
            ->filter(); // remove null products in case they were deleted

        return response()->json([
            'success' => true,
            'favorites' => array_values($favorites->toArray())
        ]);
    }

    /**
     * Search and List Vendors
     */
    public function searchVendors(Request $request)
    {
        $query = $request->query('query');
        $vendors = Vendor::where('status', 'approved');

        if ($query) {
            $vendors->where('store_name', 'LIKE', '%' . $query . '%');
        }

        return response()->json([
            'success' => true,
            'vendors' => $vendors->get()
        ]);
    }

    /**
     * Get Vendor Store Details & Products
     */
    public function getVendorStore(Request $request, $id)
    {
        $vendor = Vendor::where('id', $id)->where('status', 'approved')->firstOrFail();
        $products = Product::where('vendor_id', $vendor->id)->approved()->get();

        return response()->json([
            'success' => true,
            'vendor' => $vendor,
            'products' => $products
        ]);
    }
}
