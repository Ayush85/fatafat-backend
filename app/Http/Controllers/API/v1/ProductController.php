<?php

namespace App\Http\Controllers\API\v1;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Build cache key from request parameters (excluding API key for better cache hits)
            $cacheParams = $request->except(['api_key', 'API-Key']);
            $cacheKey = 'products_' . md5(json_encode($cacheParams));

            // Cache for 5 minutes
            $result = cache()->remember($cacheKey, 300, function () use ($request) {
                $query = Product::where('status', Product::STATUS_ENABLED);

                // Selective eager loading based on request
                $with = ['media']; // Always load media for images

                if ($request->filled('include')) {
                    $includes = explode(',', $request->input('include'));
                    if (in_array('brand', $includes))
                        $with[] = 'brand';
                    if (in_array('categories', $includes))
                        $with[] = 'categories';
                    if (in_array('vendor', $includes))
                        $with[] = 'vendor';
                    if (in_array('variants', $includes))
                        $with[] = 'variants.media';
                }

                $query->with($with);

                // Search functionality
                if ($request->filled('search') || $request->filled('name')) {
                    $search = '%' . ($request->input('search') ?? $request->input('name')) . '%';
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', $search)
                            ->orWhere('highlights', 'like', $search)
                            ->orWhere('description', 'like', $search)
                            ->orWhere('short_description', 'like', $search);
                    });
                }

                // Filter by category
                if ($request->filled('category_id')) {
                    $query->whereHas('categories', function ($q) use ($request) {
                        $q->where('product_categories.id', $request->category_id);
                    });
                }

                // Filter by brand
                if ($request->filled('brand_id')) {
                    $query->where('brand_id', $request->brand_id);
                }

                // Price range filter
                if ($request->filled('min_price')) {
                    $query->where('price', '>=', $request->min_price);
                }
                if ($request->filled('max_price')) {
                    $query->where('price', '<=', $request->max_price);
                }

                // Featured products
                if ($request->filled('is_featured')) {
                    $query->where('is_featured', $request->is_featured);
                }

                // Sorting
                if ($request->filled('sort')) {
                    switch ($request->sort) {
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
                            $query->orderBy('id', 'desc');
                    }
                } else {
                    $query->orderBy('id', 'desc');
                }

                $perPage = $request->input('per_page', 10);
                $products = $query->paginate($perPage);

                return [
                    'success' => true,
                    'data' => ProductResource::collection($products),
                    'meta' => [
                        'current_page' => $products->currentPage(),
                        'per_page' => $products->perPage(),
                        'total' => $products->total(),
                        'last_page' => $products->lastPage(),
                    ]
                ];
            });

            return response()->json($result);

        } catch (\Exception $e) {
            // For development/demo purposes, return mock data when database is not available
            if (app()->environment('local') && str_contains($e->getMessage(), 'No connection could be made')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        [
                            'id' => 1,
                            'name' => 'iPhone 15 Pro',
                            'slug' => 'iphone-15-pro',
                            'sku' => 'IPH15PRO',
                            'short_description' => 'Latest iPhone with advanced features',
                            'description' => 'The iPhone 15 Pro features a titanium design, A17 Pro chip, and advanced camera system.',
                            'price' => 149999,
                            'original_price' => 159999,
                            'discounted_price' => 149999,
                            'quantity' => 50,
                            'unit' => 'piece',
                            'status' => 1,
                            'is_featured' => true,
                            'average_rating' => 4.5,
                            'brand' => [
                                'id' => 1,
                                'name' => 'Apple',
                                'slug' => 'apple'
                            ],
                            'categories' => [
                                [
                                    'id' => 2,
                                    'title' => 'Smartphones',
                                    'slug' => 'smartphones'
                                ]
                            ],
                            'created_at' => '2024-01-01T00:00:00.000000Z',
                            'updated_at' => '2024-01-01T00:00:00.000000Z'
                        ]
                    ],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => 20,
                        'total' => 1,
                        'last_page' => 1,
                    ],
                    'message' => 'Mock data returned (database not connected)'
                ]);
            }
            return $this->errorResponse('An error occurred while retrieving products: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with(['brand', 'categories', 'vendor', 'variants.media', 'reviews.user', 'media'])
                ->find($id);

            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            return $this->successResponse(new ProductResource($product));

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function showBySlug($slug)
    {
        try {
            $product = Product::with(['brand', 'categories', 'vendor', 'variants.media', 'reviews.user', 'media'])
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

    public function productDetail($slug)
    {
        return $this->showBySlug($slug);
    }

    public function search(Request $request)
    {
        // This is essentially the same as index but with different naming
        return $this->index($request);
    }
}
