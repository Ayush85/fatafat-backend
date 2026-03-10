<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductModel extends BaseModel
{
    protected $table = 'products';

    const STATUS_ENABLED = 1;

    const STATUS_DISABLED = 0;

    const STATUS_DRAFT = 2;

    protected $casts = [
        'status' => 'boolean',
        'emi_enabled' => 'boolean',
        'pre_order' => 'boolean',
        'is_featured' => 'boolean',
        'attributes' => 'array',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrandModel::class, 'brand_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategoryModel::class, 'categories_products', 'product_id', 'product_category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariantModel::class, 'product_id');
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->using(FileUsageModel::class)
            ->whereIn('file_usages.usage_type', ['products'])
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }

    public function defaultFile(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->using(FileUsageModel::class)
            ->whereIn('file_usages.usage_type', ['products'])
            ->where(static function ($query) {
                $query->whereRaw("JSON_EXTRACT(file_usages.meta, '$.is_default') = true")
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(file_usages.meta, '$.is_default'))) = 'true'");
            })
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->orderByPivot('id', 'desc');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeClassModel::class, 'attribute_class_id');
    }
}
