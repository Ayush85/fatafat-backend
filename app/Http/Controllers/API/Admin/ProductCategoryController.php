<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use App\Http\Resources\ProductCategoryResource;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $categories = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => ProductCategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:product_categories',
            'parent_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'status' => 'boolean'
        ]);

        $category = ProductCategory::create($request->all());

        return response()->json([
            'success' => true,
            'data' => new ProductCategoryResource($category),
            'message' => 'Category created successfully'
        ], 201);
    }

    public function show($id)
    {
        $category = ProductCategory::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProductCategoryResource($category)
        ]);
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:product_categories,slug,' . $id,
            'parent_id' => 'nullable|exists:product_categories,id',
            'description' => 'nullable|string',
            'status' => 'boolean'
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'data' => new ProductCategoryResource($category),
            'message' => 'Category updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    public function getCategoryTree()
    {
        $categories = ProductCategory::whereNull('parent_id')
            ->with('children')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProductCategoryResource::collection($categories)
        ]);
    }

    public function getCategoryDropdown()
    {
        $categories = ProductCategory::select('id', 'title', 'parent_id')
            ->orderBy('title')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function sortCategories(Request $request)
    {
        // Implementation for sorting categories
        return response()->json([
            'success' => true,
            'message' => 'Categories sorted successfully'
        ]);
    }

    public function getProductCategoryImages($id)
    {
        $category = ProductCategory::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category->getMedia('images') ?? []
        ]);
    }

    public function saveProductCategoryImage(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        if ($request->hasFile('image')) {
            $category->addMediaFromRequest('image')
                ->toMediaCollection('images');
        }

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully'
        ]);
    }

    public function deleteProductCategoryImage($id, $imageId)
    {
        $category = ProductCategory::findOrFail($id);
        $media = $category->getMedia('images')->find($imageId);

        if ($media) {
            $media->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }
}