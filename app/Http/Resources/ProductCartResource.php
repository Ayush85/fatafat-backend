<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultFile = null;
        if ($this->relationLoaded('defaultFile')) {
            $defaultFile = $this->defaultFile->first();
        }

        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'img' => [
                'url' => $defaultFile?->url,
                'alt_text' => $defaultFile?->pivot?->alt_text,
            ],
        ];
    }
}

