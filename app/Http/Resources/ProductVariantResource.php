<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'attributes' => $this->attributes,
            'images' => $this->relationLoaded('files')
                ? $this->files->map(function ($file) {
                    return [
                        'url' => $file->url,
                        'alt_text' => $file->pivot?->alt_text,
                    ];
                })->values()
                : [],
        ];
    }
}
