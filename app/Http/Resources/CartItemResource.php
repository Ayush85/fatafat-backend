<?php

namespace App\Http\Resources;

use App\Http\Resources\ProductCartResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'quantity' => (int)$this->quantity,
            'price' => (float)$this->price,
            'product_attributes' => $this->product_attributes,
            'product' => new ProductCartResource($this->whenLoaded('product')),
            'subtotal' => (float)$this->price * (int)$this->quantity,
        ];
    }
}
