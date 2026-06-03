<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultFile = null;
        if ($this->relationLoaded('defaultFile')) {
            $defaultFile = $this->defaultFile->first();
        }

        if (in_array($request->route()?->getName(), ['product.list', 'products.search', 'category.products.by.slug'], true)) {
            return $this->listResponse($defaultFile);
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => [
                'short_description' => $this->short_description,
                'description' => $this->description,
                'highlights' => $this->highlights,
                'warranty_description' => $this->warranty_description,
            ],

            'price' => [
                'original_price' => $this->original_price,
                'current' => $this->price,
            ],

            'quantity' => $this->quantity,

            'status' => $this->status,

            'attributes' => is_array($this->attributes)
                ? ($this->attributes['product_attributes'] ?? $this->attributes)
                : $this->attributes,

            'emi_enabled' => $this->emi_enabled,
            'pre_order' => [
                'available' => $this->pre_order,
                'price' => $this->pre_order_price,
            ],

            'thumb' => [
                'url' => $defaultFile?->url,
                'alt_text' => $defaultFile?->pivot?->alt_text,
            ],

            'images' => $this->relationLoaded('files')
                ? $this->files->map(function ($file) {
                    return [
                        'url' => $file->url,
                        'alt_text' => $file->pivot?->alt_text,
                    ];
                })->values()
                : [],
            'brand' => new ProductBrandResource($this->whenLoaded('brand')),

            'categories' => ProductCategoryResource::collection($this->whenLoaded('categories')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'reviews' => ProductReviewResource::collection($this->whenLoaded('reviews')),
            'meta' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ],
            'faqs' => $this->relationLoaded('faqs')
                ? $this->faqs->map(function ($faq) {
                    return [
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ];
                })->values()
                : []

        ];
    }

    public function listResponse($defaultFile): array
    {
        $brand = null;
        if ($this->relationLoaded('brand') && $this->brand) {
            $brand = [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'thumb' => $this->resolveBrandThumb($this->brand),
            ];
        }

        $categories = [];
        if ($this->relationLoaded('categories')) {
            $categories = $this->categories->map(function ($category) {
                return [
                    'name' => $category->title,
                    'slug' => $category->slug,
                ];
            })->values()->all();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'highlights' => $this->highlights,
            'thumb' => [
                'url' => $defaultFile?->url,
                'alt_text' => $defaultFile?->pivot?->alt_text,
            ],
            'price' => $this->price,
            'quantity' => $this->quantity,
            'brand' => $brand,
            'categories' => $categories,
            'sku' => $this->sku,
            'emi_enabled' => $this->emi_enabled,
            'pre_order' => [
                'available' => $this->pre_order,
                'price' => $this->pre_order_price,
            ],
            'short_desc' => $this->short_description,
            'meta' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ],
        ];
    }

    private function resolveBrandThumb($brand): ?string
    {
        if ($brand->relationLoaded('defaultFile')) {
            $defaultFile = $brand->defaultFile->first();

            if ($defaultFile?->url) {
                return $defaultFile->url;
            }
        }

        if ($brand->relationLoaded('files')) {
            return $brand->files->first(fn($item) => !empty($item->url))?->url;
        }

        return null;
    }

    public function discountedListResponse($defaultFile, $campaignProduct, $campaignSlug = null): array
    {
        $brand = null;
        if ($this->relationLoaded('brand') && $this->brand) {
            $brand = [
                'name'  => $this->brand->name,
                'slug'  => $this->brand->slug,
                'thumb' => $this->resolveBrandThumb($this->brand),
            ];
        }

        $categories = [];
        if ($this->relationLoaded('categories')) {
            $categories = $this->categories->map(function ($category) {
                return [
                    'name' => $category->title,
                    'slug' => $category->slug,
                ];
            })->values()->all();
        }

        $currentPrice    = $this->price;
        $discountType    = $campaignProduct->discount_type_label; // 'fixed' or 'percentage'
        $discountValue   = $campaignProduct->discount_value;
        $discountAmount  = $discountType === 'fixed'
            ? $discountValue
            : round($currentPrice * $discountValue / 100, 2);
        $discountedPrice = max(0, $currentPrice - $discountAmount);

        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'highlights' => $this->highlights,
            'thumb'      => [
                'url'      => $defaultFile?->url,
                'alt_text' => $defaultFile?->pivot?->alt_text,
            ],
            'price'      => [
                'current'    => $currentPrice,
                'discounted' => $discountedPrice,
            ],
            'discount'   => [
                'type'          => $discountType,
                'value'         => $discountValue,
                'amount'        => $discountAmount,
                'campaign_slug' => $campaignSlug,
            ],
            'quantity'   => $this->quantity,
            'brand'      => $brand,
            'categories' => $categories,
            'sku'        => $this->sku,
            'emi_enabled' => $this->emi_enabled,
            'pre_order'  => [
                'available' => $this->pre_order,
                'price'     => $this->pre_order_price,
            ],
            'short_desc' => $this->short_description,
            'meta'       => [
                'meta_title'       => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords'    => $this->meta_keywords,
            ],
        ];
    }
}
