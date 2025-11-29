<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBrand;
use Illuminate\Http\Request;

class ProductBrandController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductBrand::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $brands = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $brands,
            'meta' => [
                'current_page' => $brands->currentPage(),
                'per_page' => $brands->perPage(),
                'total' => $brands->total(),
                'last_page' => $brands->lastPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:product_brands',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'status' => 'boolean'
        ]);

        $brand = ProductBrand::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $brand,
            'message' => 'Brand created successfully'
        ], 201);
    }

    public function show($id)
    {
        $brand = ProductBrand::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $brand
        ]);
    }

    public function update(Request $request, $id)
    {
        $brand = ProductBrand::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:product_brands,slug,' . $id,
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'status' => 'boolean'
        ]);

        $brand->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $brand,
            'message' => 'Brand updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $brand = ProductBrand::findOrFail($id);
        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully'
        ]);
    }

    public function getBrandsDropdown()
    {
        $brands = ProductBrand::select('id', 'name')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $brands
        ]);
    }

    public function getProductBrandImages($id)
    {
        $brand = ProductBrand::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $brand->getMedia('images') ?? []
        ]);
    }

    public function saveProductBrandImage(Request $request, $id)
    {
        $brand = ProductBrand::findOrFail($id);

        if ($request->hasFile('image')) {
            $brand->addMediaFromRequest('image')
                ->toMediaCollection('images');
        }

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully'
        ]);
    }

    public function deleteProductBrandImage($id, $imageId)
    {
        $brand = ProductBrand::findOrFail($id);
        $media = $brand->getMedia('images')->find($imageId);

        if ($media) {
            $media->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }
}