<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'order_status' => $this->order_status,
            'order_total' => $this->order_total,
            'shipping_cost' => $this->shipping_cost,
            'discounts_total' => $this->discounts_total,
            'total' => $this->total,
            'payment_type' => $this->payment_type,
            'discount_coupon' => $this->discount_coupon,
            'cancel_reason' => $this->cancel_reason,
            'user' => new UserResource($this->whenLoaded('user')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'shipping_address' => new UserShippingAddressResource($this->whenLoaded('shippingAddress')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
