<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductBrand extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'meta_title',
        'meta_keywords',
        'meta_description'
    ];

    protected $appends = ['brand_image'];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->fit('contain', 400, 400)
            ->sharpen(10)
            ->format('webp');
    }

    public function getBrandImageAttribute()
    {
        $first_media = $this->getFirstMedia();
        $placeholder = asset('/website/images/placeholder-lg.png');
        $placeholderSm = asset('/website/images/placeholder-sm.png');

        if (!$first_media) {
            return [
                "full" => $placeholder,
                "thumb" => $placeholderSm,
            ];
        }

        return [
            "full" => $first_media->getUrl(),
            "thumb" => $first_media->hasGeneratedConversion('thumb') ? $first_media->getUrl('thumb') : $first_media->getUrl(),
        ];
    }
}
