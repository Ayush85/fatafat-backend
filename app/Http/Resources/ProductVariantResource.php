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
        ];
    }
}
