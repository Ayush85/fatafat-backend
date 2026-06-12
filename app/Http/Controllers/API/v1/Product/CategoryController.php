<?php

namespace App\Http\Controllers\API\v1\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductBrandResource;
use App\Http\Resources\ProductCategoryDetailResponse;
use App\Http\Resources\ProductCategoryListResponse;
use App\Http\Resources\RelatedProductResource;
use App\Models\ProductBrandModel;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
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
                ->with(['defaultFile', 'files', 'products.brand.defaultFile', 'products.brand.files']);

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
                return $this->successResponse(ProductCategoryListResponse::collection($categories));
            }

            $perPage = $request->input('per_page', 20);
            $categories = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ProductCategoryListResponse::collection($categories),
                'meta' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
           
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

            return $this->successResponse(new ProductCategoryDetailResponse($category));

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
                'banners',
                'faqs',
                'products' => function ($query) {
                    $query->where('status', ProductModel::STATUS_ENABLED)
                        ->whereNull('products.deleted_at')
                        ->with(['brand.defaultFile', 'brand.files', 'defaultFile']);
                },
            ])
                ->where('slug', $slug)
                ->first();

            if (!$category) {
                return $this->errorResponse('Category not found', 404);
            }

            $categoryData = (new ProductCategoryDetailResponse($category))->toArray(request());
            $categoryData['banners'] = $category->relationLoaded('banners')
                ? $category->banners->map(static function ($banner) {
                    $pivotMeta = $banner->pivot?->meta;

                    if (!is_array($pivotMeta)) {
                        $pivotMeta = json_decode((string) $pivotMeta, true);
                    }

                    if (!is_array($pivotMeta)) {
                        $pivotMeta = [];
                    }

                    return [
                        'id' => $banner->pivot?->id ?? $banner->id,
                        'url' => $banner->url,
                        'status' => $pivotMeta['status'] ?? false,
                        'start_date' => $pivotMeta['start_date'] ?? null,
                        'end_date' => $pivotMeta['end_date'] ?? null,
                        'redirect_url' => $pivotMeta['redirect_url'] ?? null,
                    ];
                })->values()
                : [];
            $categoryData['related_brands'] = $this->relatedBrandsForCategory($category);

            return response()->json([
                'success' => true,
                'message' => 'Category retrieved successfully',
                'data' => $categoryData,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    private function relatedBrandsForCategory(ProductCategoryModel $category)
    {
        return ProductBrandModel::query()
            ->where('status', 1)
            ->whereHas('products', function ($query) use ($category) {
                $query->where('products.status', ProductModel::STATUS_ENABLED)
                    ->whereNull('products.deleted_at')
                    ->whereHas('categories', function ($query) use ($category) {
                        $query->where('product_categories.id', $category->id);
                    });
            })
            ->with(['defaultFile', 'files'])
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($brand) use ($category) {
                $products = ProductModel::query()
                    ->where('products.status', ProductModel::STATUS_ENABLED)
                    ->whereNull('products.deleted_at')
                    ->where('products.brand_id', $brand->id)
                    ->whereHas('categories', function ($query) use ($category) {
                        $query->where('product_categories.id', $category->id);
                    })
                    ->with(['brand.defaultFile', 'brand.files', 'categories', 'defaultFile'])
                    ->orderBy('name', 'asc')
                    ->limit(6)
                    ->get();

                $brandData = (new ProductBrandResource($brand))->toArray(request());
                $brandData['products'] = RelatedProductResource::collection($products);

                return $brandData;
            })
            ->values();
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
