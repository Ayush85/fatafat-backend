<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Blog extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'title',
        'slug',
        'short_desc',
        'content',
        'author',
        'is_featured',
        'status',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'category_id',
        'publish_date',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'publish_date' => 'datetime',
    ];

    protected $appends = ['thumbnail_image'];

    public function category()
    {
        return $this->belongsTo(BlogCategory::class);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->sharpen(10); // Maintain aspect ratio within 400x400

        $this->addMediaConversion('preview')
            ->nonQueued()
            ->sharpen(10)
            ->format('webp');
    }

    public function getThumbnailImageAttribute()
    {
        $media = $this->getFirstMedia();

        if ($media) {
            return [
                "full" => $media->getUrl(),
                "thumb" => $media->getUrl('thumb'),
                "preview" => $media->getUrl('preview'),
            ];
        }

        return [
            "full" => asset('/website/images/placeholder-landscape.png'),
            "thumb" => asset('/website/images/placeholder-landscape.png'),
            "preview" => asset('/website/images/placeholder-landscape.png'),
        ];
    }
}
