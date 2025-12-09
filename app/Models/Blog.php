<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use HasFactory, SoftDeletes;

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

    public function images()
    {
        return $this->hasMany(BlogImage::class)->orderBy('display_order', 'asc');
    }

    public function primaryImage()
    {
        return $this->hasOne(BlogImage::class)->where('is_primary', true);
    }

    /**
     * Get thumbnail image URLs (for API responses)
     * Returns multiple image sizes like the reference package
     */
    public function getThumbnailImageAttribute()
    {
        $defaultImage = asset('/website/images/placeholder-landscape.png');
        
        $primaryImage = $this->primaryImage()->first();
        
        if ($primaryImage) {
            return [
                'full' => $primaryImage->image_url,
                'thumb' => $primaryImage->image_url,
                'preview' => $primaryImage->image_url,
            ];
        }

        return [
            'full' => $defaultImage,
            'thumb' => $defaultImage,
            'preview' => $defaultImage,
        ];
    }
}
