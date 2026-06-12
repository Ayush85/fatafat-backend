<?php

namespace App\Http\Controllers\API\v1\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
     *
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
            $perPage = min((int) $request->input('per_page', 10), 100);
            $query = $this->buildProductQuery($request);

            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => ProductResource::collection($products),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while retrieving products: '.$e->getMessage(), 500);
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
            $product = ProductModel::with(['brand.defaultFile', 'brand.files', 'categories.defaultFile', 'variants.files', 'defaultFile', 'files','faqs'])
                ->where('status', ProductModel::STATUS_ENABLED)
                ->whereNull('products.deleted_at')
                ->where('slug', $slug)
                ->first();

            if (! $product) {
                return $this->errorResponse('Product not found', 404);
            }

            return $this->successResponse(new ProductResource($product));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: '.$e->getMessage(), 500);
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
        $category = ProductCategoryModel::with(['defaultFile', 'files'])
            ->where('slug', $slug)
            ->first();

        if (! $category) {
            return $this->errorResponse('Category not found', 404);
        }

        $request->merge([
            'category' => $slug,
            'category_id' => $category->id,
        ]);

        $perPage = (int) $request->input('per_page', 10);
        $products = $this->buildProductQuery($request)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => (new ProductCategoryResource($category))->toArray($request),
                'products' => ProductResource::collection($products->items()),
            ],
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function search(Request $request)
    {
        $request->merge(['include' => $request->input('include', 'brand,categories')]);
        $perPage = min((int) $request->input('per_page', 20), 50);

        try {
            $products = $this->buildProductQuery($request)->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => ProductResource::collection($products),
                'meta'    => [
                    'current_page' => $products->currentPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                    'last_page'    => $products->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Search failed: '.$e->getMessage(), 500);
        }
    }

    private function buildProductQuery(Request $request): Builder
    {
        $includes = collect(explode(',', (string) $request->input('include', '')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->values();

        $with = ['defaultFile'];
        if ($includes->contains('brand'))      { $with[] = 'brand.defaultFile'; $with[] = 'brand.files'; }
        if ($includes->contains('categories')) { $with[] = 'categories'; }

        $query = ProductModel::where('status', ProductModel::STATUS_ENABLED)
            ->whereNull('products.deleted_at')
            ->with($with)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term  = trim((string) $request->search);
                $lower = mb_strtolower($term, 'UTF-8');
                $like  = '%'.$term.'%';

                $q->where(function ($sq) use ($like) {
                    $sq->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('sku',  'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('short_description', 'like', $like)
                        ->orWhere('highlights', 'like', $like)
                        ->orWhere('price', 'like', $like)
                        ->orWhereRaw('CAST(attributes AS CHAR) LIKE ?', [$like]);
                });

                $q->orderByRaw(
                    'CASE
                        WHEN LOWER(name) = ? THEN 1
                        WHEN LOWER(name) LIKE ? THEN 2
                        WHEN LOWER(name) LIKE ? THEN 3
                        WHEN LOWER(slug) LIKE ? THEN 4
                        WHEN LOWER(sku)  LIKE ? THEN 5
                        WHEN LOWER(slug) LIKE ? THEN 6
                        WHEN LOWER(sku)  LIKE ? THEN 7
                        ELSE 8
                    END',
                    [$lower, $lower.'%', '%'.$lower.'%', $lower.'%', $lower.'%', '%'.$lower.'%', '%'.$lower.'%']
                );
                $q->orderByRaw("CASE WHEN LOWER(name) LIKE '%pro max%' THEN 3 WHEN LOWER(name) LIKE '%pro%' THEN 2 ELSE 1 END ASC");
                $q->orderByRaw(
                    "CASE
                        WHEN LOWER(name) REGEXP '[0-9]+[[:space:]]*tb' THEN CAST(REGEXP_SUBSTR(LOWER(name),'[0-9]+') AS UNSIGNED)*1024
                        WHEN LOWER(name) REGEXP '[0-9]+[[:space:]]*gb' THEN CAST(REGEXP_SUBSTR(LOWER(name),'[0-9]+') AS UNSIGNED)
                        ELSE 0
                    END DESC"
                );
            })
            ->when($request->filled('category'), function ($q) use ($request) {
                $q->whereHas('categories', fn ($cq) =>
                    $cq->where('product_categories.slug', $request->category)
                );
            })
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('categories', fn ($cq) =>
                    $cq->where('product_categories.id', (int) $request->category_id)
                );
            })
            ->when($request->filled('brand'), function ($q) use ($request) {
                $slugs = array_values(array_filter(array_map('trim', explode(',', (string) $request->brand))));
                if (count($slugs) === 1) {
                    $q->whereHas('brand', fn ($bq) => $bq->where('slug', $slugs[0]));
                } elseif (count($slugs) > 1) {
                    $q->whereHas('brand', fn ($bq) => $bq->whereIn('slug', $slugs));
                }
            })
            ->when($request->filled('min_price'), function ($q) use ($request) {
                $q->where('price', '>=', (float) $request->min_price);
            })
            ->when($request->filled('max_price'), function ($q) use ($request) {
                $q->where('price', '<=', (float) $request->max_price);
            })
            ->when($request->filled('is_featured'), function ($q) use ($request) {
                $q->where('is_featured', $request->boolean('is_featured'));
            })
            ->when($request->filled('emi_enabled'), function ($q) use ($request) {
                $q->where('emi_enabled', $request->boolean('emi_enabled'));
            })
            ->when($request->filled('pre_order'), function ($q) use ($request) {
                $q->where('pre_order', $request->boolean('pre_order'));
            })
            ->when($request->boolean('in_stock'), function ($q) {
                $q->where('quantity', '>', 0);
            })
            ->when($request->boolean('on_sale'), function ($q) {
                $q->whereNotNull('original_price')
                  ->whereColumn('original_price', '>', 'price');
            });

        match ($request->input('sort', 'newest')) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'name_desc'  => $query->orderBy('name', 'desc'),
            default      => $query->orderByDesc('created_at'),
        };

        return $query;
    }
}
