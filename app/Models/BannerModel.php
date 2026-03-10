<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BannerImageModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BannerModel extends Model
{
    protected $table = 'banners';

    protected $casts = [
        'status' => 'boolean',
    ];

    public function bannerImages()
    {
        return $this->hasMany(BannerImageModel::class, 'banner_id');
    }

    public function images()
    {
        return $this->bannerImages();
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->using(FileUsageModel::class)
            ->whereIn('file_usages.usage_type', ['banners'])
            ->withPivot(['id', 'usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->withTimestamps();
    }

    public function defaultFile(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'file_usages', 'usage_id', 'file_id')
            ->using(FileUsageModel::class)
            ->whereIn('file_usages.usage_type', ['banners'])
            ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(file_usages.meta, '$.collection_name'))) = 'default'")
            ->withPivot(['id', 'usage_type', 'usage_id', 'title', 'alt_text', 'meta'])
            ->orderByPivot('id', 'desc');
    }
}
