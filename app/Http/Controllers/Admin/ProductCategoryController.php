<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\File;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::orderBy('id', 'desc')->get();
        return view('admin.product_categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|image|max:2048'
        ]);

        $data = ['name' => $request->name];

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('/uploads/product_categories');
            
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            
            $image->move($destinationPath, $name);
            $data['image'] = $name; // Store filename as in old system
        }

        ProductCategory::create($data);

        return back()->with('success', 'Category added successfully!');
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048'
        ]);

        $category->name = $request->name;

        if ($request->hasFile('image')) {
            // Delete old image
            if ($category->image && File::exists(public_path('/uploads/product_categories/' . $category->image))) {
                File::delete(public_path('/uploads/product_categories/' . $category->image));
            }

            $image = $request->file('image');
            $name = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('/uploads/product_categories');
            
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            
            $image->move($destinationPath, $name);
            $category->image = $name;
        }

        $category->save();

        return back()->with('success', 'Category updated successfully!');
    }

    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);

        if ($category->image && File::exists(public_path('/uploads/product_categories/' . $category->image))) {
            File::delete(public_path('/uploads/product_categories/' . $category->image));
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully!');
    }
}
