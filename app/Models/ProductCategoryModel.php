<?php

declare(strict_types=1);

namespace App\Models;


use App\Models\BaseModel;
use App\Models\FileModel;
use App\Models\ProductModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategoryModel extends BaseModel
{
    use SoftDeletes;

    protected $table = 'product_categories';

    protected $casts = [
        'status' => 'boolean',
    ];

    // TODO: Define table, fillable, casts, relations.

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ProductModel::class, 'categories_products', 'product_category_id', 'product_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'product_categories')
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }

    public function defaultFile(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'product_categories')
            ->where(static function ($query) {
                $query->whereRaw("JSON_EXTRACT(file_usages.meta, '$.is_default') = true")
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(file_usages.meta, '$.is_default'))) = 'true'");
            })
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->orderByPivot('id', 'asc');
    }
     public function banners(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'product_categories')
            ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(file_usages.meta, '$.type'))) = 'banner'")
            ->withPivot(['id', 'usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }
    public function faqs()
    {
        return $this->hasMany(Faq::class, 'type_id')->where('type', 'category');
    }
}
