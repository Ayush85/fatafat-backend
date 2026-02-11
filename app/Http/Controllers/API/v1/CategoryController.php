<?php

namespace App\Http\Controllers\API\v1;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;

class CategoryController extends Controller
{
    /**
     * List all categories
     *
     * Get a list of product categories with hierarchical support.
     *
     * @group Categories
     *
     * @queryParam parent_id integer Filter by parent category ID. Example: 1
     * @queryParam root boolean Get only root categories (no parent). Example: 1
     * @queryParam featured boolean Filter by featured status. Example: 1
     * @queryParam with_children boolean Include children categories. Example: 1
     * @queryParam with_parent boolean Include parent category. Example: 1
     * @queryParam paginate boolean Enable or disable pagination (default: true). Set to 'false' for all items. Example: true
     * @queryParam per_page integer The number of items per page. Example: 20
     */
    public function index(Request $request)
    {
        try {
            $query = ProductCategory::where('status', 1)
                ->with(['media']);

            // Filter by parent
            if ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            } elseif ($request->has('root')) {
                $query->whereNull('parent_id');
            }

            // Featured categories
            if ($request->filled('featured')) {
                $query->where('featured', $request->featured);
            }

            // Load relationships
            if ($request->filled('with_children')) {
                $query->with('children');
            }

            if ($request->filled('with_parent')) {
                $query->with('parent');
            }

            $query->orderBy('order', 'asc')->orderBy('title', 'asc');

            if ($request->filled('paginate') && $request->paginate == 'false') {
                $categories = $query->get();
                return $this->successResponse(ProductCategoryResource::collection($categories));
            }

            $perPage = $request->input('per_page', 20);
            $categories = $query->paginate($perPage);

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

        } catch (\Exception $e) {
            // For development/demo purposes, return mock data when database is not available
            if (app()->environment('local') && str_contains($e->getMessage(), 'No connection could be made')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        [
                            'id' => 1,
                            'title' => 'Electronics',
                            'slug' => 'electronics',
                            'description' => 'Electronic items and gadgets',
                            'parent_id' => null,
                            'status' => 1,
                            'featured' => true,
                            'order' => 1,
                            'category_full_name' => 'Electronics',
                            'image' => null,
                            'images' => []
                        ],
                        [
                            'id' => 2,
                            'title' => 'Smartphones',
                            'slug' => 'smartphones',
                            'description' => 'Mobile phones and accessories',
                            'parent_id' => 1,
                            'status' => 1,
                            'featured' => true,
                            'order' => 1,
                            'category_full_name' => 'Electronics / Smartphones',
                            'image' => null,
                            'images' => []
                        ]
                    ],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => 20,
                        'total' => 2,
                        'last_page' => 1,
                    ],
                    'message' => 'Mock data returned (database not connected)'
                ]);
            }
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $category = ProductCategory::with(['parent', 'children', 'media'])->find($id);

            if (!$category) {
                return $this->errorResponse('Category not found', 404);
            }

            return $this->successResponse(new ProductCategoryResource($category));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function showBySlug($slug)
    {
        try {
            $category = ProductCategory::with(['parent', 'children', 'media'])
                ->where('slug', $slug)
                ->first();

            if (!$category) {
                return $this->errorResponse('Category not found', 404);
            }

            return $this->successResponse(new ProductCategoryResource($category));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function parentCategories()
    {
        return $this->index(request()->merge(['root' => true, 'paginate' => 'false']));
    }

    public function navbarItems()
    {
        // Return root categories with children, similar to parentCategories but specifically for navbar
        // Matching openapi spec requirement for /categorys/navbarItems
        return $this->index(request()->merge(['root' => true, 'with_children' => true, 'paginate' => 'false']));
    }
}
