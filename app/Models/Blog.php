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
        // Check if the media file exists and return the URLs accordingly
        $fullImageUrl = $this->getFirstMediaUrl();
        $thumbImageUrl = ($first_media = $this->getFirstMedia()) ? $first_media->getUrl('thumb') : null;

        // Convert the URLs to file paths
        $fullImagePath = $fullImageUrl ? public_path(parse_url($fullImageUrl, PHP_URL_PATH)) : null;
        $thumbImagePath = $thumbImageUrl ? public_path(parse_url($thumbImageUrl, PHP_URL_PATH)) : null;

        // Check if the full image exists using file_exists
        $fullImageExists = $fullImagePath && file_exists($fullImagePath);

        // Check if the thumbnail exists using file_exists
        $thumbImageExists = $thumbImagePath ? file_exists($thumbImagePath) : false;

        return [
            "full" => $fullImageExists ? $fullImageUrl : asset('/website/images/placeholder-landscape.png'),
            "thumb" => $thumbImageExists ? $thumbImageUrl : asset('/website/images/placeholder-landscape.png'),
            "preview" => $thumbImageExists ? $thumbImageUrl : asset('/website/images/placeholder-landscape.png'),
        ];
    }
}
