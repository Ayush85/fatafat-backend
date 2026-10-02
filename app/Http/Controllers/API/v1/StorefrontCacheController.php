<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Called by the admin panel after it changes catalogue data, so customers see
 * the change without waiting for the API cache and the Next.js page cache to expire.
 */
class StorefrontCacheController extends Controller
{
    private const TYPES = ['product', 'category', 'brand', 'blog', 'all'];

    public function invalidate(Request $request): JsonResponse
    {
        $expected = (string) config('storefront.invalidate_secret');
        $given = (string) $request->header('X-Storefront-Secret', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.type' => ['required', 'string', 'in:' . implode(',', self::TYPES)],
            'items.*.slug' => ['nullable', 'string', 'max:255'],
        ]);

        $categorySlugs = [];
        $revalidate = [];

        foreach ($validated['items'] as $item) {
            $type = $item['type'];
            $slug = $item['slug'] ?? null;

            if ($type === 'category' && $slug) {
                $categorySlugs[] = $slug;
            } elseif ($type === 'product' && $slug) {
                $categorySlugs = array_merge($categorySlugs, $this->categorySlugsForProduct($slug));
            }

            $revalidate[] = ['type' => $type, 'slug' => $slug];
        }

        foreach (array_unique($categorySlugs) as $categorySlug) {
            Cache::forget('category:detail:' . $categorySlug);
        }
        Cache::forget('categories:navbar_items');

        $this->notifyStorefront($revalidate);

        return response()->json(['success' => true, 'cleared_categories' => count(array_unique($categorySlugs))]);
    }

    private function categorySlugsForProduct(string $productSlug): array
    {
        $product = ProductModel::withTrashed()->where('slug', $productSlug)->with('categories.parent')->first();
        if (! $product) {
            return [];
        }

        $slugs = [];
        foreach ($product->categories as $category) {
            $slugs[] = $category->slug;
            if ($category->parent) {
                $slugs[] = $category->parent->slug;
            }
        }

        return array_filter($slugs);
    }

    private function notifyStorefront(array $items): void
    {
        $url = config('storefront.revalidate_url');
        $secret = config('storefront.revalidate_secret');
        if (! $url || ! $secret) {
            return;
        }

        // The storefront endpoint takes one item per call; collapse big batches.
        if (count($items) > 20) {
            $items = [['type' => 'all', 'slug' => null]];
        }

        foreach ($items as $item) {
            try {
                Http::timeout(5)->acceptJson()->post($url, [
                    'secret' => $secret,
                    'type' => $item['type'],
                    'slug' => $item['slug'],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Storefront revalidate failed', ['item' => $item, 'error' => $e->getMessage()]);
            }
        }
    }
}
