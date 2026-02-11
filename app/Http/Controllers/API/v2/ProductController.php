<?php

namespace App\Http\Controllers\API\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jed\Ecommerce\App\Product;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function search(Request $request)
    {
        try {
            $query = $request->input('query');
            $products = Product::enabled();

            if ($query) {
                $search = '%' . $query . '%';
                $products = $products->where(function ($q) use ($search) {
                    $q->where('name', 'like', $search)
                        ->orWhere('highlights', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhere('short_description', 'like', $search)
                        ->orWhereRaw("JSON_SEARCH(attributes, 'all', ?) IS NOT NULL", [$search]);
                });
            }

            $perPage = $request->input('per_page', 20);
            $paginatedProducts = $products->paginate($perPage);

            return response()->json([
                'data' => ProductResource::collection($paginatedProducts),
                'meta' => [
                    'current_page' => $paginatedProducts->currentPage(),
                    'last_page' => $paginatedProducts->lastPage(),
                    'per_page' => $paginatedProducts->perPage(),
                    'total' => $paginatedProducts->total(),
                ]
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching products.',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $products = Product::enabled();

            if ($request->has('name')) {
                $search = '%' . $request->input('name') . '%';
                $products = $products->where(function ($query) use ($search) {
                    $query->where('name', 'like', $search)
                        ->orWhere('highlights', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhere('short_description', 'like', $search)
                        ->orWhereRaw("JSON_SEARCH(attributes, 'all', ?) IS NOT NULL", [$search]);
                });
            }

            // Additional filters from v1/search
            if ($request->filled('min_price')) {
                $products->where('price', '>=', $request->min_price);
            }
            if ($request->filled('max_price')) {
                $products->where('price', '<=', $request->max_price);
            }
            if ($request->filled('brand')) {
                $products->where('brand', $request->brand);
            }

            if ($request->filled('sort')) {
                switch ($request->sort) {
                    case 'price_asc':
                        $products->orderBy('price');
                        break;
                    case 'price_desc':
                        $products->orderByDesc('price');
                        break;
                    case 'newest':
                        $products->orderByDesc('created_at');
                        break;
                }
            }

            $perPage = $request->input('per_page', 20);
            $paginatedProducts = $products->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ProductResource::collection($paginatedProducts),
                'meta' => [
                    'current_page' => $paginatedProducts->currentPage(),
                    'per_page' => $paginatedProducts->perPage(),
                    'total' => $paginatedProducts->total(),
                    'last_page' => $paginatedProducts->lastPage(),
                ]
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving products.',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function showBySlug($slug)
    {
        $product = Product::where("slug", $slug)->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }
}
