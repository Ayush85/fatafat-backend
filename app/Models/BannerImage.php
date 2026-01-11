<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BannerImage extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        "link",
        "content",
        "start_date",
        "end_date",
        "banner_id",
        "order"
    ];

    protected $appends = ['banner_image'];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->width(400)
            ->height(400)
            ->sharpen(10);

        $this->addMediaConversion('banner')
            ->nonQueued()
            ->width(600)
            ->height(450)
            ->sharpen(10)
            ->format('webp');
    }

    public function getBannerImageAttribute()
    {
        $firstMedia = $this->getFirstMedia();

        return [
            "full" => $firstMedia ? $firstMedia->getUrl() : null,
            "thumb" => $firstMedia ? $firstMedia->getUrl('thumb') : null,
            "banner" => $firstMedia && $firstMedia->hasGeneratedConversion('banner')
                ? $firstMedia->getUrl('banner')
                : ($firstMedia ? $firstMedia->getUrl('thumb') : null)
        ];
    }
}
