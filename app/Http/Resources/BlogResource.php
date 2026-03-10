<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultFile = $this->getDefaultFile();

        if ($request->route()?->getName() === 'api.blogs.show') {
            return $this->showResponse($defaultFile, $request);
        }

        return $this->listResponse($defaultFile);
    }

    public function toListArray(): array
    {
        return $this->listResponse($this->getDefaultFile());
    }

    private function getDefaultFile()
    {
        if ($this->relationLoaded('defaultFile')) {
            return $this->defaultFile->first();
        }

        return null;
    }

    private function listResponse($defaultFile): array
    {
        $category = null;
        if ($this->relationLoaded('category') && $this->category) {
            $category = [
                'name' => $this->category->title,
                'slug' => $this->category->slug,
            ];
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_desc' => $this->short_desc,
            'thumb' => $defaultFile
                ? [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text,
                ]
                : null,
            'publish_date' => $this->publish_date,
            'author' => $this->author,
            'category' => $category,
        ];
    }

    private function showResponse($defaultFile, Request $request): array
    {
        $category = null;
        if ($this->relationLoaded('category') && $this->category) {
            $category = [
                'name' => $this->category->title,
                'slug' => $this->category->slug,
            ];
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_desc' => $this->short_desc,
            'content' => $this->content,
            'author' => $this->author,
            'is_featured' => (bool) $this->is_featured,
            'status' => (bool) $this->status,
            'published_date' => $this->publish_date,
            'category' => $category,
            'thumb' => $defaultFile
                ? [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text,
                ]
                : null,
            'meta' => [
                'title' => $this->meta_title,
                'keyword' => $this->meta_keywords,
                'description' => $this->meta_description,
            ],
            'published_at' => $this->publish_date,
        ];
    }
}
