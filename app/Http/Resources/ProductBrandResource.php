<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultFile = null;
        if ($this->relationLoaded('defaultFile')) {
            $defaultFile = $this->defaultFile->first();
        }

        if ($request->route()?->getName() === 'brands.show') {
            return $this->showResponse($defaultFile, $request);
        }

        return $this->listResponse($defaultFile);
    }

    private function listResponse($defaultFile): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'thumb' => $defaultFile
                ? [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text,
                ]
                : null,
            'slug' => $this->slug,
            'status' => $this->status,
        ];
    }

    private function showResponse($defaultFile, Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'thumb' => $defaultFile
                ? [
                    'url' => $defaultFile->url,
                    'alt_text' => $defaultFile->pivot?->alt_text,
                ]
                : null,
        ];

        if ($request->boolean('show_description')) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
