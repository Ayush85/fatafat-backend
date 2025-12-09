<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'is_primary' => $this->is_primary,
            'alt_text' => $this->alt_text,
        ];
    }
}
