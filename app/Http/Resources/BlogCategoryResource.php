<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class BlogCategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        $defaultFile = null;
        if ($this->relationLoaded('defaultFile')) {
            $defaultFile = $this->defaultFile->first();
        }

        if ($request->route()?->getName() === 'api.blogs.show') {
            return $this->showResponse($defaultFile);
        }

        return $this->listResponse($defaultFile);
    }

    private function listResponse($defaultFile): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'thumb' => $defaultFile
                ? [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text,
                ]
                : null,
            'blogs_count' => $this->blogs_count,
            'status' => (bool) $this->status,
        ];
    }

    private function showResponse($defaultFile): array
    {
        $data = $this->resource->toArray();
        $files = [];

        if ($this->relationLoaded('files')) {
            $files = $this->files->map(static function ($file) {
                $meta = $file->pivot?->meta;
                if (is_string($meta)) {
                    $decoded = json_decode($meta, true);
                    $meta = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
                }
                if (!is_array($meta)) {
                    $meta = [];
                }

                return [
                    'id' => $file->id,
                    'url' => $file->url,
                    'title' => $file->pivot?->title,
                    'alt_text' => $file->pivot?->alt_text,
                    'meta' => $meta,
                    'file_size' => is_numeric($file->file_size ?? null) ? (float) $file->file_size : null,
                    'size' => is_numeric($file->file_size ?? null) ? (float) $file->file_size : null,
                    'height' => is_numeric($file->height ?? null) ? (float) $file->height : null,
                    'width' => is_numeric($file->width ?? null) ? (float) $file->width : null,
                ];
            })->values()->all();
        }

        $data['thumb'] = $defaultFile
            ? [
                'url' => $defaultFile->url,
                'alt_text' => $defaultFile->pivot?->alt_text,
            ]
            : null;
        $data['status'] = (bool) ($data['status'] ?? $this->status);
        $data['default_file'] = $defaultFile?->toArray();
        $data['files'] = $files;

        return $data;
    }
}
