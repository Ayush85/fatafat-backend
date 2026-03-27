<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $thumb = $this->resolveThumb($this);

        if (in_array($request->route()?->getName(), ['category.by.id', 'category.by.slug','category.products.by.slug'], true)) {
            return $this->detailResponse($thumb);
        }

        return $this->listResponse($thumb);
    }

    private function listResponse(?array $thumb): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'thumb' => $thumb,
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
                ->filter(static fn ($price) => is_numeric($price))
                ->map(static fn ($price) => (float) $price)
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
                    $defaultFile = $brand->relationLoaded('defaultFile')
                        ? $brand->defaultFile->first()
                        : null;

                    return [
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'thumb' => $defaultFile
                            ? [
                                'url' => $defaultFile->url,
                                'alt_text' => $defaultFile->pivot?->alt_text,
                            ]
                            : null,
                    ];
                });
        }

        return $data;
    }

    private function detailResponse(?array $thumb): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'status' => (bool) $this->status,
            'featured' => (bool) ($this->featured ?? false),
            'order' => $this->order,
            'parent_tree' => $this->parent_tree,
            'thumb' => $thumb,
            'parent' => $this->relationLoaded('parent') && $this->parent
                ? [
                    'id' => $this->parent->id,
                    'title' => $this->parent->title,
                    'slug' => $this->parent->slug,
                    'thumb' => $this->resolveThumb($this->parent),
                ]
                : null,
            'children' => $this->relationLoaded('children')
                ? $this->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'title' => $child->title,
                        'slug' => $child->slug,
                        'status' => (bool) $child->status,
                        'thumb' => $this->resolveThumb($child),
                    ];
                })->values()
                : [],
            'meta' =>[
                "title" => $this->meta_title,
                "keywords" => $this->meta_keywords,
                "description" => $this->meta_description,
            ]
        ];

        if ($this->relationLoaded('products')) {
            $prices = $this->products
                ->pluck('price')
                ->filter(static fn ($price) => is_numeric($price))
                ->map(static fn ($price) => (float) $price)
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
                    $defaultFile = $brand->relationLoaded('defaultFile')
                        ? $brand->defaultFile->first()
                        : null;

                    return [
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'thumb' => $defaultFile
                            ? [
                                'url' => $defaultFile->url,
                                'alt_text' => $defaultFile->pivot?->alt_text,
                            ]
                            : null,
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
            $file = $category->files->first(fn ($item) => !empty($item->url));
            if ($file) {
                return [
                    'url' => $file->url,
                    'alt_text' => $file?->alt_text,
                ];
            }
        }

        return null;
    }
}
