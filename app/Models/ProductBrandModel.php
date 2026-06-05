<?php

declare(strict_types=1);

namespace App\Models;



use App\Models\BaseModel;
use App\Models\FileModel;
use App\Models\ProductModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBrandModel extends BaseModel
{
    use SoftDeletes;

    protected $table = 'product_brands';

    protected $casts = [
        'status' => 'boolean',
    ];

    // TODO: Define table, fillable, casts, relations.

    public function products()
    {
        return $this->hasMany(ProductModel::class, 'brand_id');
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'product_brands')
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }

    public function defaultFile(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'product_brands')
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
            ->wherePivot('usage_type', 'product_brands')
            ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(file_usages.meta, '$.type'))) = 'banner'")
            ->withPivot(['id', 'usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }
    public function faqs()
    {
        return $this->hasMany(Faq::class, 'type_id')->where('type', 'brand');
    }
}
