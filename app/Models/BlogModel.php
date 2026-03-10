<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BaseModel;
use App\Models\BlogCategoryModel;
use App\Models\FileModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogModel extends BaseModel
{
    protected $table = 'blogs';

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategoryModel::class, 'category_id');
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'blogs')
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }

    public function defaultFile(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->wherePivot('usage_type', 'blogs')
            ->where(static function ($query) {
                $query->whereRaw("JSON_EXTRACT(file_usages.meta, '$.is_default') = true")
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(file_usages.meta, '$.is_default'))) = 'true'");
            })
            ->withPivot(['usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->orderByPivot('id', 'asc');
    }
}
