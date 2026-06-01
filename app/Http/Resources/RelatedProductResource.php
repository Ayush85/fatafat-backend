<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelatedProductResource extends JsonResource
{
    private bool $includeVariants = false;

    public function includeVariants(bool $includeVariants = true): self
    {
        $this->includeVariants = $includeVariants;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $defaultFile = $this->relationLoaded('defaultFile')
            ? $this->defaultFile->first()
            : null;

        // Match the same payload used by product listings (e.g. category products list).
        $productPayload = (new ProductResource($this))->listResponse($defaultFile);

        $shouldShowVariants = $this->includeVariants
            || (bool) $request->attributes->get('include_related_variants', false)
            || $request->boolean('show_product_variants');

        if (! $shouldShowVariants) {
            return $productPayload;
        }

        $variants = $this->relationLoaded('variants') ? $this->variants : collect();
        $productPayload['variants'] = ProductVariantResource::collection($variants)->toArray($request);

        return $productPayload;
    }
}
