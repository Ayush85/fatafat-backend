<?php

declare(strict_types=1);

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ProductResource;

class CampaignResource extends JsonResource
{
    // ── Shared base fields ────────────────────────────────────────────────────

    private function baseFields(): array
    {
        $current_time = now();
        $is_active = $this->is_published &&
            $this->start_date <= $current_time &&
            $this->end_date >= $current_time;

        $thumb = null;
        if ($this->relationLoaded('defaultFile')) {
            $defaultFile = $this->defaultFile->first();
            if ($defaultFile) {
                $thumb = [
                    'url'      => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text ?? $defaultFile->alt_text,
                ];
            }
        }

        return [
            'name'       => $this->title,
            'slug'       => $this->slug,
            'start_date' => $this->start_date,
            'end_date'   => $this->end_date,
            'status'     => $is_active ? 'active' : 'inactive',
            'thumb'      => $thumb,
        ];
    }

    // ── List response — products_count only ───────────────────────────────────

    public function listResponse(): array
    {
        return array_merge($this->baseFields(), [
            'products_count' => $this->products_count ?? 0,
        ]);
    }

    // ── Detail response — paginated products ──────────────────────────────────

    public function detailResponse(Request $request): array
    {
        $perPage  = (int) $request->get('per_page', 15);
        $page     = (int) $request->get('page', 1);
        $products = $this->relationLoaded('products')
            ? $this->products->forPage($page, $perPage)->values()
            : collect();

        $productData = $products->map(function ($campaignProduct) {
            $product = $campaignProduct->product;
            if (! $product) {
                return null;
            }
            $defaultFile = $product->relationLoaded('defaultFile')
                ? $product->defaultFile->first()
                : null;
            return (new ProductResource($product))->discountedListResponse($defaultFile, $campaignProduct, $this->slug);
        })->filter()->values();

        return array_merge($this->baseFields(), [
            'products' => [
                'data'         => $productData,
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $this->relationLoaded('products') ? $this->products->count() : 0,
            ],
        ]);
    }

    // ── Default toArray (fallback) ─────────────────────────────────────────────

    public function toArray($request): array
    {
        return $this->baseFields();
    }
}
