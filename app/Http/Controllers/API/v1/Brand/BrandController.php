<?php

namespace App\Http\Controllers\API\v1\Brand;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductBrandResource;
use App\Http\Resources\RelatedProductResource;
use App\Models\ProductBrandModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;

/**
 * @group Brands
 *
 * Brand listing and detail endpoints.
 */
class BrandController extends Controller
{
    /**
     * Brand List
     *
     * @name Brand List
     *
     * @queryParam paginate boolean Set `true` to enable pagination. Example: true
     * @queryParam per_page integer Number of items per page when pagination is enabled. Example: 20
     * @queryParam sort string Sort by brand name using `asc` or `desc`. Example: desc
     */
    public function index(Request $request)
    {
        try {
            $sort = strtolower((string) $request->input('sort', 'asc'));
            $direction = $sort === 'desc' ? 'desc' : 'asc';

            $query = ProductBrandModel::where('status', 1)
                ->with('defaultFile')
                ->orderBy('name', $direction);

            if ($request->boolean('paginate')) {
                $perPage = $request->input('per_page', 20);
                $brands = $query->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'data' => ProductBrandResource::collection($brands),
                    'meta' => [
                        'current_page' => $brands->currentPage(),
                        'per_page' => $brands->perPage(),
                        'total' => $brands->total(),
                        'last_page' => $brands->lastPage(),
                    ],
                ]);
            }

            $brands = $query->get();

            return response()->json([
                'success' => true,
                'data' => ProductBrandResource::collection($brands),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }


    /**
     * Product Brand Detail By Slug
     *
     * @name Product Brand Detail By Slug
     *
     * @queryParam show_description boolean Include brand description in the detail response. Example: true
     * @queryParam show_product_variants boolean Include product variants in related products. This parameter is ignored if `product_ram`, `product_storage`, or `product_color` is provided. Example: false
     * @queryParam product_per_page integer Number of related products per page. Example: 20
     * @queryParam product_sort string Sort related products by name using `asc` or `desc`. Example: asc
     * @queryParam product_min_price number Filter related products by minimum price. Example: 64999
     * @queryParam product_max_price number Filter related products by maximum price. Example: 99999
     * @queryParam product_color string Filter related products by color. Example: silver
     * @queryParam product_storage string Filter related products by storage variant attribute. Example: 64gb
     * @queryParam product_ram string Filter related products by RAM variant attribute. Example: 4 GB
     * @queryParam product_in_stock boolean Filter related products by stock availability. Example: true
     */
    public function showBySlug(Request $request, $slug)
    {
        try {
            $brand = ProductBrandModel::with('defaultFile')
                ->where('slug', $slug)
                ->where('status', 1)
                ->first();

            if (!$brand) {
                return $this->errorResponse('Brand not found', 404);
            }

            $sort = strtolower((string) $request->input('product_sort', 'asc'));
            $direction = $sort === 'desc' ? 'desc' : 'asc';
            $perPage = (int) $request->input('product_per_page', 12);
            $normalizeVariantFilter = static function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }

                $normalized = strtolower(trim($value));
                $normalized = str_replace([' ', '-', '_'], '', $normalized);

                return $normalized === '' ? null : $normalized;
            };

            $variantFilters = array_filter([
                'Color' => $request->filled('product_color')
                    ? $normalizeVariantFilter((string) $request->input('product_color'))
                    : null,
                'STORAGE' => $request->filled('product_storage')
                    ? $normalizeVariantFilter((string) $request->input('product_storage'))
                    : null,
                'RAM' => $request->filled('product_ram')
                    ? $normalizeVariantFilter((string) $request->input('product_ram'))
                    : null,
            ]);
            $inStockFilter = $request->filled('product_in_stock')
                ? $request->boolean('product_in_stock')
                : null;
            $shouldShowVariants = $request->boolean('show_product_variants') || !empty($variantFilters);

            $productsQuery = ProductModel::query()
                ->where('status', ProductModel::STATUS_ENABLED)
                ->whereNull('products.deleted_at')
                ->where('brand_id', $brand->id)
                ->with([
                    'defaultFile',
                    'brand.defaultFile',
                    'categories',
                    'variants' => function ($query) use ($variantFilters, $inStockFilter) {
                        $query->with('files');
                        foreach ($variantFilters as $attributeKey => $attributeValue) {
                            $query->whereRaw(
                                'REPLACE(REPLACE(REPLACE(LOWER(JSON_UNQUOTE(JSON_EXTRACT(attributes, ?))), " ", ""), "-", ""), "_", "") = ?',
                                ['$.'.$attributeKey, $attributeValue]
                            );
                        }

                        if ($inStockFilter !== null && !empty($variantFilters)) {
                            if ($inStockFilter) {
                                $query->where('quantity', '>', 0);
                            } else {
                                $query->where('quantity', '<=', 0);
                            }
                        }
                    }
                ])
                ->orderBy('name', $direction);

            if ($request->filled('product_min_price')) {
                $productsQuery->where('price', '>=', $request->input('product_min_price'));
            }

            if ($request->filled('product_max_price')) {
                $productsQuery->where('price', '<=', $request->input('product_max_price'));
            }

            if ($inStockFilter !== null) {
                if (!empty($variantFilters)) {
                    $productsQuery->whereHas('variants', function ($query) use ($variantFilters, $inStockFilter) {
                        foreach ($variantFilters as $attributeKey => $attributeValue) {
                            $query->whereRaw(
                                'REPLACE(REPLACE(REPLACE(LOWER(JSON_UNQUOTE(JSON_EXTRACT(attributes, ?))), " ", ""), "-", ""), "_", "") = ?',
                                ['$.'.$attributeKey, $attributeValue]
                            );
                        }

                        if ($inStockFilter) {
                            $query->where('quantity', '>', 0);
                        } else {
                            $query->where('quantity', '<=', 0);
                        }
                    });
                } elseif ($inStockFilter) {
                    $productsQuery->where('quantity', '>', 0);
                } else {
                    $productsQuery->where('quantity', '<=', 0);
                }
            }

            if (!empty($variantFilters) && $inStockFilter === null) {
                $productsQuery->whereHas('variants', function ($query) use ($variantFilters) {
                    foreach ($variantFilters as $attributeKey => $attributeValue) {
                        $query->whereRaw(
                            'REPLACE(REPLACE(REPLACE(LOWER(JSON_UNQUOTE(JSON_EXTRACT(attributes, ?))), " ", ""), "-", ""), "_", "") = ?',
                            ['$.'.$attributeKey, $attributeValue]
                        );
                    }
                });
            }

            $products = $productsQuery->paginate($perPage);

            $request->attributes->set('include_related_variants', $shouldShowVariants);

            return response()->json([
                'success' => true,
                'data' => new ProductBrandResource($brand),
                'related_products' => RelatedProductResource::collection($products->getCollection()->values()),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
                'message' => 'Brand retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }
}
