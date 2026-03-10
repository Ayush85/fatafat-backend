<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray($request): array
    {
        $defaultFile = null;
        if ($this->relationLoaded('defaultFile')) {
            $defaultFile = $this->defaultFile->first();
        }

        $isDetailRoute = $request->route()?->getName() === 'banner.detail';
        $hasDetailRelations = $this->relationLoaded('files');

        if ($isDetailRoute || $hasDetailRelations) {
            return $this->detailPayload($defaultFile);
        }

        return $this->listPayload($defaultFile);
    }

    public static function listResponse($banners): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => self::collection($banners),
        ];

        if ($banners instanceof LengthAwarePaginator) {
            $response['meta'] = [
                'current_page' => $banners->currentPage(),
                'per_page' => $banners->perPage(),
                'total' => $banners->total(),
                'last_page' => $banners->lastPage(),
            ];
        }

        return response()->json($response);
    }

    public static function detailResponse($banner): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new self($banner),
        ]);
    }

    // Backward-compatible alias for typo usage.
    public static function listRespnose($banners): JsonResponse
    {
        return self::listResponse($banners);
    }

    private function listPayload($defaultFile): array
    {
        $totalImages = isset($this->files_count)
            ? (int) $this->files_count
            : ($this->relationLoaded('files') ? $this->files->count() : 0);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => (bool) $this->status,
            'total_images' => $totalImages,
            'thumb' => $defaultFile
                ? [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text,
                ]
                : null,
        ];
    }

    private function detailPayload($defaultFile): array
    {
        $files = [];
        if ($this->relationLoaded('files')) {
            $files = $this->files->map(static function ($file) {
                $pivotMeta = is_array($file->pivot?->meta) ? $file->pivot?->meta : [];
               
                return [
                    'id' => $file->pivot?->id,
                    'url' => $file->url,
                    'status' => $pivotMeta['is_active'] ?? false,
                    'alt_text' => $file->pivot?->alt_text,
                    'start_date' => $pivotMeta['start_date'] ?? null,
                    'end_date' => $pivotMeta['end_date'] ?? null,
                    'link' => $pivotMeta['link'] ?? null,
                ];
            })->values()->all();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => (bool) $this->status,
            'images' => $files,
        ];
    }
}
