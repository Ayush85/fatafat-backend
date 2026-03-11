<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariantModel extends BaseModel
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id', 'price', 'quantity', 'attributes',
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

     public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'product_variants')
            ->withPivot(['id', 'usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }

    public function defaultFile(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'product_variants')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(file_usages.meta, '$.collection_name')) = ?", ['default'])
            ->withPivot(['id', 'usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->orderByPivot('id', 'asc')
            ->limit(1);
    }
}
