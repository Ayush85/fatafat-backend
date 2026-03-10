<?php

namespace App\Http\Controllers\API\v1\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Products
 *
 * Product listing, search, and detail endpoints.
 */
class ProductController extends Controller
{
    /**
     * Product List
     *
     * Get a list of products with optional filtering, sorting, and searching.
     *
     * @group Products
     * @name Product List
     *
     * @queryParam category string Filter by category slug. Example: smartphones
     * @queryParam brand string Filter by brand slug. Example: apple
     * @queryParam search string Search by product name, slug, sku, description, price, attributes, short description, or highlights. Example: iphone
     * @queryParam min_price number Filter by minimum price. Example: 100
     * @queryParam max_price number Filter by maximum price. Example: 1000
     * @queryParam is_featured boolean Filter by featured status. Example: true
     * @queryParam emi_enabled boolean Filter by EMI availability. Example: true
     * @queryParam pre_order boolean Filter by pre-order availability. Example: true
     * @queryParam sort string Sort option (price_asc, price_desc, name_asc, name_desc, newest). Example: newest
     * @queryParam include string Comma-separated list of relationships to include (`brand`, `categories`). Example: brand,categories
     * @queryParam per_page integer The number of items per page. Example: 15
     * @queryParam page integer The current page number. Example: 1
     */
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->input('per_page', 10);
            $includes = collect(explode(',', (string) $request->input('include', '')))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->values();

            $with = ['defaultFile'];
            if ($includes->contains('brand')) {
                $with[] = 'brand.defaultFile';
            }
            if ($includes->contains('categories')) {
                $with[] = 'categories';
            }

            $query = ProductModel::where('status', ProductModel::STATUS_ENABLED)
                ->with($with)
                // Search across product name, description, price, attributes JSON, short description, and highlights.
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->search);
                    $likeSearch = '%' . $search . '%';

                    $query->where(function ($searchQuery) use ($search, $likeSearch) {
                        $searchQuery->where('name', 'like', $likeSearch)
                            ->orWhere('slug', 'like', $likeSearch)
                            ->orWhere('sku', 'like', $likeSearch)
                            ->orWhere('description', 'like', $likeSearch)
                            ->orWhere('short_description', 'like', $likeSearch)
                            ->orWhere('highlights', 'like', $likeSearch)
                            ->orWhere('price', 'like', $likeSearch)
                            ->orWhereRaw('CAST(attributes AS CHAR) LIKE ?', [$likeSearch]);
                    });
                })
                // Filter products by related category slug.
                ->when($request->filled('category'), function ($query) use ($request) {
                    $query->whereHas('categories', function ($categoryQuery) use ($request) {
                        $categoryQuery->where('product_categories.slug', $request->category);
                    });
                })
                // Filter products by related brand slug.
                ->when($request->filled('brand'), function ($query) use ($request) {
                    $query->whereHas('brand', function ($brandQuery) use ($request) {
                        $brandQuery->where('slug', $request->brand);
                    });
                })
                // Filter products with price greater than or equal to min_price.
                ->when($request->filled('min_price'), function ($query) use ($request) {
                    $query->where('price', '>=', $request->min_price);
                })
                // Filter products with price less than or equal to max_price.
                ->when($request->filled('max_price'), function ($query) use ($request) {
                    $query->where('price', '<=', $request->max_price);
                })
                // Filter products by featured status.
                ->when($request->filled('is_featured'), function ($query) use ($request) {
                    $query->where('is_featured', $request->boolean('is_featured'));
                })
                // Filter products by EMI availability.
                ->when($request->filled('emi_enabled'), function ($query) use ($request) {
                    $query->where('emi_enabled', $request->boolean('emi_enabled'));
                })
                // Filter products by pre-order availability.
                ->when($request->filled('pre_order'), function ($query) use ($request) {
                    $query->where('pre_order', $request->boolean('pre_order'));
                });

                switch ($request->input('sort')) {
                    case 'price_asc':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'price_desc':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'name_asc':
                        $query->orderBy('name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('name', 'desc');
                        break;
                    case 'newest':
                        $query->orderBy('created_at', 'desc');
                        break;
                    default:
                        $query->orderByDesc('created_at');
                        break;
                }

            $products = $query->paginate($perPage);

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

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving products: ' . $e->getMessage(), 500);
        }
    }

   
    /**
     * Get Product By Slug
     *
     * @name Get Product By Slug
     */
    public function showBySlug($slug)
    {
        try {
            $product = ProductModel::with(['brand.defaultFile', 'categories.defaultFile', 'variants.files', 'defaultFile', 'files'])
                ->where('slug', $slug)
                ->first();

            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            return $this->successResponse(new ProductResource($product));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

   

    /**
     * List Products By Category
     *
     * @name List Products By Category
     */
    public function getByCategory(Request $request, $id)
    {
        $request->merge(['category_id' => $id]);
        return $this->index($request);
    }

    /**
     * List Products By Category Slug
     *
     * @name List Products By Category Slug
     */
    public function categoryProducts(Request $request, $slug)
    {
        $category = ProductCategoryModel::where('slug', $slug)->first();

        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }

        $request->merge(['category_id' => $category->id]);

        return $this->index($request);
    }
}
