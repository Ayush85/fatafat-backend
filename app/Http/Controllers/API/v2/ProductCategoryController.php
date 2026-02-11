<?php

namespace App\Http\Controllers\API\v2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jed\Ecommerce\App\ProductCategory;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;

class ProductCategoryController extends Controller
{
    public function navbarItems()
    {
        // Assuming 'children' relationship exists and is recursive or covers immediate children.
        // openapi spec shows a nested structure.
        $categories = ProductCategory::whereNull('parent_id') // Get root categories
            ->with('children') // Load immediate children
            ->get();

        // Transform to match openapi example structure if needed, or use resource.
        // The spec shows: id, name, slug, children[].

        return response()->json([
            'data' => CategoryResource::collection($categories)
        ]);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = ProductCategory::with('children');

        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $perPage = $request->input('per_page', 10);
        $categories = $query->paginate($perPage);

        return response()->json([
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    public function show($slug, Request $request)
    {
        $category = ProductCategory::where('slug', $slug)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 10);

        $productsQuery = $category->products();
        $total = $productsQuery->count();
        $offset = ($page - 1) * $perPage;
        $products = $productsQuery->skip($offset)->take($perPage)->get();
        $lastPage = (int) ceil($total / $perPage);

        return response()->json([
            'category' => (new CategoryResource($category))->showDescription(),
            'products' => [
                'data' => ProductResource::collection($products),
                'meta' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'from' => $offset + 1,
                    'to' => $offset + count($products),
                ],
            ],
        ]);
    }
}
