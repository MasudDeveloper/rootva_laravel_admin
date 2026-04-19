<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->orderBy('id', 'desc')->paginate(25);
        $categories = ProductCategory::orderBy('name', 'asc')->get();
        return view('admin.products.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required',
            'price' => 'required|numeric',
            'reselling_price' => 'required|numeric',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048'
        ]);

        $data = [
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'reselling_price' => $request->reselling_price,
            'created_at' => now()->toDateTimeString(),
        ];

        $images = [];
        if ($request->hasFile('images')) {
            $destinationPath = public_path('/uploads/products');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }

            foreach ($request->file('images') as $image) {
                $name = time() . '_' . uniqid() . '_' . $image->getClientOriginalName();
                $image->move($destinationPath, $name);
                $images[] = '/uploads/products/' . $name;
            }
        }

        // Maintain backward compatibility: use first image as 'image' field
        if (count($images) > 0) {
            $data['image'] = $images[0];
            $data['images'] = $images;
        }

        Product::create($data);

        return back()->with('success', 'Product added to Reselling Shop!');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required',
            'price' => 'required|numeric',
            'reselling_price' => 'required|numeric',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048'
        ]);

        $product->category_id = $request->category_id;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->reselling_price = $request->reselling_price;

        if ($request->hasFile('images')) {
            // Delete old images if they exist
            if ($product->images && is_array($product->images)) {
                foreach ($product->images as $oldImage) {
                    $oldPath = str_starts_with($oldImage, '/')
                        ? public_path($oldImage)
                        : public_path('uploads/products/' . $oldImage);
                    
                    if (File::exists($oldPath)) {
                        File::delete($oldPath);
                    }
                }
            }

            $destinationPath = public_path('/uploads/products');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }

            $images = [];
            foreach ($request->file('images') as $image) {
                $name = time() . '_' . uniqid() . '_' . $image->getClientOriginalName();
                $image->move($destinationPath, $name);
                $images[] = '/uploads/products/' . $name;
            }

            if (count($images) > 0) {
                $product->image = $images[0];
                $product->images = $images;
            }
        }

        $product->save();

        return back()->with('success', 'Product updated successfully!');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image) {
            // নতুন format: /uploads/products/filename
            // পুরনো format: শুধু filename
            $imagePath = str_starts_with($product->image, '/')
                ? public_path($product->image)
                : public_path('uploads/products/' . $product->image);

            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }
        }
        $product->delete();
        return back()->with('success', 'Product deleted successfully!');
    }
}
