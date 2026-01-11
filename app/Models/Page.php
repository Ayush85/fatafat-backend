<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Page extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_keywords',
        'meta_description'
    ];

    protected $appends = ['thumbnail_image'];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->width(400)
            ->height(400)
            ->sharpen(10);
    }

    public function getThumbnailImageAttribute()
    {
        $firstMedia = $this->getFirstMedia();
        $placeholderLg = asset('/website/images/placeholder-lg.png');
        $placeholderSm = asset('/website/images/placeholder-sm.png');

        return [
            "full" => $firstMedia ? $firstMedia->getUrl() : $placeholderLg,
            "thumb" => $firstMedia ? $firstMedia->getUrl('thumb') : $placeholderSm
        ];
    }
}
