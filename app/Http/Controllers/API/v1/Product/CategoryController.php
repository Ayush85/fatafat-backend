<?php

namespace App\Http\Controllers\API\v1\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategoryModel;
use Illuminate\Http\Request;

/**
 * @group Categories
 *
 * Category listing and detail endpoints.
 */
class CategoryController extends Controller
{
    /**
     * Product Category List
     *
     * Get a list of product categories with hierarchical support.
     *
     * @group Categories
     * @name Category List
     *
     * @queryParam category_id integer Filter by parent category ID. Example: 1
     * @queryParam root boolean Get only root categories (no parent). Example: true
     * @queryParam featured boolean Filter by featured status. Example: true
     * @queryParam with_children boolean Include children categories. Example: true
     * @queryParam with_parent boolean Include parent category. Example: true
     * @queryParam paginate boolean Enable or disable pagination. Example: true
     * @queryParam per_page integer The number of items per page. Example: 20
     */
    public function index(Request $request)
    {
        try {
            $query = ProductCategoryModel::where('status', 1)
                ->with(['defaultFile', 'files']);

            if ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            } elseif ($request->has('root')) {
                $query->whereNull('parent_id');
            }

            if ($request->filled('featured')) {
                $query->where('featured', $request->featured);
            }

            if ($request->filled('with_children')) {
                $query->with(['children.defaultFile', 'children.files']);
            }

            if ($request->filled('with_parent')) {
                $query->with(['parent.defaultFile', 'parent.files']);
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

    /**
     * Get Category By ID
     *
     * @name Get Category By ID
     */
    public function show($id)
    {
        try {
            $category = ProductCategoryModel::with([
                'parent.defaultFile',
                'parent.files',
                'children.defaultFile',
                'children.files',
                'defaultFile',
                'files',
            ])->find($id);

            if (!$category) {
                return $this->errorResponse('Category not found', 404);
            }

            return $this->successResponse(new ProductCategoryResource($category));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Category By Slug
     *
     * @name Get Category By Slug
     */
    public function showBySlug($slug)
    {
        try {
            $category = ProductCategoryModel::with([
                'parent.defaultFile',
                'parent.files',
                'children.defaultFile',
                'children.files',
                'defaultFile',
                'files',
            ])
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

    /**
     * List Parent Categories
     *
     * @name List Parent Categories
     */
    public function parentCategories()
    {
        return $this->index(request()->merge(['root' => true, 'paginate' => 'false']));
    }

    /**
     * List Navbar Categories
     *
     * @name List Navbar Categories
     */
    public function navbarItems()
    {
        return $this->index(request()->merge(['root' => true, 'with_children' => true, 'paginate' => 'false']));
    }
}
