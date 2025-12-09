<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'status' => $this->status,
            'featured' => $this->featured,
            'order' => $this->order,
            'category_full_name' => $this->category_full_name,
            'image' => new CategoryImageResource($this->whenLoaded('primaryImage')),
            'images' => CategoryImageResource::collection($this->whenLoaded('images')),
            'parent' => new ProductCategoryResource($this->whenLoaded('parent')),
            'children' => ProductCategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
