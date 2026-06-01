<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'discount_coupon' => $this->discount_coupon,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'cart_total' => $this->getCartItemTotal(),
        ];
    }
}
