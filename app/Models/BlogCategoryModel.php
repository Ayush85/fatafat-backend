<?php

declare(strict_types=1);

namespace App\Models;


use App\Models\BaseModel;
use App\Models\BlogModel;
use App\Models\FileModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategoryModel extends BaseModel
{
    protected $table = 'blog_categories';


    protected $casts = [
        'status' => 'boolean',
    ];

    public function blogs(): HasMany
    {
        return $this->hasMany(BlogModel::class, 'category_id');
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'blog_categories')
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }

    public function defaultFile(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'blog_categories')
            ->where(static function ($query) {
                $query->whereRaw("JSON_EXTRACT(file_usages.meta, '$.is_default') = true")
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(file_usages.meta, '$.is_default'))) = 'true'");
            })
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->orderByPivot('id', 'asc');
    }
}
