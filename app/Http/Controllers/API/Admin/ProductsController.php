<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'categories', 'vendor', 'variants']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('product_categories.id', $request->category_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }

    public function show($id)
    {
        $product = Product::with(['brand', 'categories', 'vendor', 'variants', 'reviews'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product)
        ]);
    }

    public function storeProductDetail(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products',
            'price' => 'required|numeric|min:0',
            'vendor_id' => 'required|exists:vendors,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:product_categories,id'
        ]);

        $product = Product::create($request->all());

        if ($request->filled('category_ids')) {
            $product->categories()->sync($request->category_ids);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
            'message' => 'Product created successfully'
        ], 201);
    }

    public function updateProductDetail(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $id,
            'price' => 'required|numeric|min:0',
            'vendor_id' => 'required|exists:vendors,id',
            'brand_id' => 'nullable|exists:product_brands,id',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:product_categories,id'
        ]);

        $product->update($request->all());

        if ($request->filled('category_ids')) {
            $product->categories()->sync($request->category_ids);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
            'message' => 'Product updated successfully'
        ]);
    }

    public function updateMetaFields(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update($request->only([
            'short_description',
            'description',
            'highlights',
            'specifications',
            'meta_title',
            'meta_description'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Product meta updated successfully'
        ]);
    }

    public function duplicateProduct(Request $request)
    {
        $originalProduct = Product::findOrFail($request->product_id);

        $duplicate = $originalProduct->replicate();
        $duplicate->name = $originalProduct->name . ' (Copy)';
        $duplicate->sku = $originalProduct->sku . '-copy';
        $duplicate->status = 0; // Draft
        $duplicate->save();

        // Copy categories
        $duplicate->categories()->sync($originalProduct->categories->pluck('id'));

        return response()->json([
            'success' => true,
            'data' => new ProductResource($duplicate),
            'message' => 'Product duplicated successfully'
        ]);
    }

    public function updateProductPrice(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'discounted_price' => 'nullable|numeric|min:0'
        ]);

        $product->update($request->only(['price', 'original_price', 'discounted_price']));

        return response()->json([
            'success' => true,
            'message' => 'Product price updated successfully'
        ]);
    }

    public function saveProductAttributes(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update(['attributes' => $request->attributes]);

        return response()->json([
            'success' => true,
            'message' => 'Product attributes updated successfully'
        ]);
    }

    public function toggleProductStatus($id)
    {
        $product = Product::findOrFail($id);

        $product->update(['status' => !$product->status]);

        return response()->json([
            'success' => true,
            'message' => 'Product status updated successfully'
        ]);
    }

    public function uploadProductImage(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $product->addMedia($image)
                    ->toMediaCollection('images');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully'
        ]);
    }

    public function getProductImages($id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product->getMedia('images')
        ]);
    }

    public function removeProductImages($imageId)
    {
        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($imageId);

        if ($media) {
            $media->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    }

    public function setPrimaryImage(Request $request, $productId, $imageId)
    {
        $product = Product::findOrFail($productId);
        $media = $product->getMedia('images')->find($imageId);

        if ($media) {
            // Remove primary from all images
            $product->getMedia('images')->each(function ($img) {
                $img->forgetCustomProperty('is_primary');
                $img->save();
            });

            // Set this as primary
            $media->setCustomProperty('is_primary', true);
            $media->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Primary image set successfully'
        ]);
    }

    public function getVendorsProducts($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);

        $products = $vendor->products()
            ->with(['brand', 'categories'])
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ]
        ]);
    }
}