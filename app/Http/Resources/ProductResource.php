<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'discounted_price' => $this->discounted_price,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'weight' => $this->weight,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'attributes' => $this->attributes,
            'variant_attributes' => $this->variant_attributes,
            'highlights' => $this->highlights,
            'product_video_url' => $this->product_video_url,
            'emi_enabled' => $this->emi_enabled,
            'pre_order' => $this->pre_order,
            'pre_order_price' => $this->pre_order_price,
            'warranty_description' => $this->warranty_description,
            'average_rating' => $this->average_rating,
            'image' => $this->default_media,
            'images' => $this->getMedia('default')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb' => $media->hasGeneratedConversion('thumbnail') ? $media->getUrl('thumbnail') : null,
                    'preview' => $media->hasGeneratedConversion('preview') ? $media->getUrl('preview') : null,
                ];
            }),
            'brand' => new ProductBrandResource($this->whenLoaded('brand')),
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'categories' => ProductCategoryResource::collection($this->whenLoaded('categories')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'reviews' => ProductReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
