<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductVariant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'original_price',
        'quantity',
        'attributes',
        'status'
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    protected $appends = ['discounted_price', 'default_media'];

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->nonQueued()
            ->fit('contain', 400, 400)
            ->sharpen(10)
            ->format('webp');

        $this->addMediaConversion('thumbnail')
            ->nonQueued()
            ->fit('contain', 200, 200)
            ->sharpen(10)
            ->format('webp');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to check variant attributes (for filtering by attribute values)
     */
    public function scopeCheckAttributes($query, $attributes = [])
    {
        foreach ($attributes as $key => $attribute) {
            $query = $query->where("attributes->{$key}", $attribute);
        }

        return $query;
    }

    public function getDiscountedPriceAttribute()
    {
        return $this->price;
    }

    public function defaultMedia()
    {
        $default_media = $this->getMedia('default', function (Media $media) {
            return isset($media->custom_properties['is_default']) && $media->custom_properties['is_default'];
        })->first();

        if (!$default_media) {
            $default_media = $this->getFirstMedia('default');
        }

        return $default_media;
    }

    public function getDefaultMediaAttribute()
    {
        $default_media = $this->defaultMedia();

        $placeholderLg = asset('website/images/placeholder-lg.png');
        $placeholderSm = asset('website/images/placeholder-sm.png');

        return [
            "full" => ($default_media) ? $default_media->getUrl() : $placeholderLg,
            "thumb" => ($default_media && $default_media->hasGeneratedConversion('thumbnail'))
                ? $default_media->getUrl('thumbnail')
                : ($default_media ? $default_media->getUrl() : $placeholderSm),
            "preview" => ($default_media && $default_media->hasGeneratedConversion('preview'))
                ? $default_media->getUrl('preview')
                : null
        ];
    }
}
