<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryListResponse extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'thumb' => $this->resolveThumb($this),
            'meta' => [
                'title' => $this->meta_title,
                'keywords' => $this->meta_keywords,
                'description' => $this->meta_description,
            ],
        ];

        if ($this->relationLoaded('parent') && $this->parent) {
            $data['parent'] = [
                'name' => $this->parent->title,
                'slug' => $this->parent->slug,
            ];
        }

        if ($this->relationLoaded('children')) {
            $data['children'] = $this->children->map(function ($child) {
                return [
                    'name' => $child->title,
                    'slug' => $child->slug,
                ];
            })->values();
        }

        if ($this->relationLoaded('products')) {
            $prices = $this->products
                ->pluck('price')
                ->filter(static fn($price) => is_numeric($price))
                ->map(static fn($price) => (float) $price)
                ->values();

            $data['price_range'] = [
                'min' => $prices->isNotEmpty() ? $prices->min() : null,
                'max' => $prices->isNotEmpty() ? $prices->max() : null,
            ];

            $data['brands'] = $this->products
                ->pluck('brand')
                ->filter()
                ->unique('id')
                ->values()
                ->map(function ($brand) {
                    return [
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'thumb' => $this->resolveBrandThumb($brand),
                    ];
                });
        }

        return $data;
    }

    private function resolveThumb($category): ?array
    {
        if ($category->relationLoaded('defaultFile')) {
            $defaultFile = $category->defaultFile->first();

            if ($defaultFile?->url) {
                return [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile?->alt_text,
                ];
            }
        }

        if ($category->relationLoaded('files')) {
            $file = $category->files->first(fn($item) => !empty($item->url));

            if ($file) {
                return [
                    'url' => $file->url,
                    'alt_text' => $file?->alt_text,
                ];
            }
        }

        return null;
    }

    private function resolveBrandThumb($brand): ?array
    {
        if ($brand->relationLoaded('defaultFile')) {
            $defaultFile = $brand->defaultFile->first();

            if ($defaultFile?->url) {
                return [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text,
                ];
            }
        }

        if ($brand->relationLoaded('files')) {
            $file = $brand->files->first(fn($item) => !empty($item->url));

            if ($file) {
                return [
                    'url' => $file->url,
                    'alt_text' => $file->pivot?->alt_text,
                ];
            }
        }

        return null;
    }
}
