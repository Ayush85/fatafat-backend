<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'short_desc' => $this->short_desc,
            'content' => $this->content,
            'author' => $this->author,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'publish_date' => $this->publish_date,
            'category_id' => $this->category_id,
            'category' => new BlogCategoryResource($this->whenLoaded('category')),
            'image' => new BlogImageResource($this->whenLoaded('primaryImage')),
            'images' => BlogImageResource::collection($this->whenLoaded('images')),
            'thumbnail_image' => $this->thumbnail_image,
            'meta_title' => $this->meta_title,
            'meta_keywords' => $this->meta_keywords,
            'meta_description' => $this->meta_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
