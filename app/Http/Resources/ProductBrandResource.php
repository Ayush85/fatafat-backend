<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $thumb = $this->resolveThumb();

        if ($request->route()?->getName() === 'brands.show') {
            return $this->showResponse($thumb, $request);
        }

        return $this->listResponse($thumb);
    }

    private function listResponse(?array $thumb): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'thumb' => $thumb,
            'slug' => $this->slug,
            'status' => $this->status,
            'meta' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ],
        ];
    }

    private function showResponse(?array $thumb, Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
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
                : [],
            'thumb' => $thumb,
        ];

        if ($request->boolean('show_description')) {
            $data['description'] = $this->description;
        }

        return $data;
    }

    private function resolveThumb(): ?array
    {
        if ($this->relationLoaded('defaultFile')) {
            $defaultFile = $this->defaultFile->first();

            if ($defaultFile?->url) {
                return [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text,
                ];
            }
        }

        if ($this->relationLoaded('files')) {
            $file = $this->files->first(fn($item) => !empty($item->url));

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
