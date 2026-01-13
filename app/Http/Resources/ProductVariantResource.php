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
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'discounted_price' => $this->discounted_price,
            'quantity' => $this->quantity,
            'attributes' => $this->attributes,
            'status' => $this->status,
            'image' => $this->default_media,
            'images' => $this->getMedia('default')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb' => $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : null,
                    'preview' => $media->hasGeneratedConversion('preview') ? $media->getUrl('preview') : null,
                ];
            }),
        ];
    }
}
