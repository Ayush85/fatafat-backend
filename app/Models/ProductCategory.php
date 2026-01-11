<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductCategory extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'product_categories';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'parent_id',
        'status',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'parent_tree',
        'featured',
        'order',
        'custom_code'
    ];

    protected $appends = ['category_full_name', 'default_image', 'image_urls'];
    protected $hidden = ['meta_title', 'meta_keywords', 'meta_description'];

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id')->with('parent');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'categories_products', 'product_category_id', 'product_id');
    }

    public function getCategoryFullNameAttribute()
    {
        return ($this->parent_tree) ? "{$this->parent_tree} / {$this->title}" : $this->title;
    }

    public function getAllChildren()
    {
        $sections = collect([]);
        foreach ($this->children as $section) {
            $sections->push($section);
            $sections = $sections->merge($section->getAllChildren());
        }
        return $sections;
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->fit('contain', 400, 400)
            ->sharpen(10)
            ->format('webp');

        $this->addMediaConversion('preview')
            ->fit('contain', 300, 300)
            ->sharpen(10)
            ->format('webp');
    }

    public function getDefaultImageAttribute()
    {
        $media = $this->getFirstMedia('default');
        $placeholder = asset('/website/images/placeholder-sm.png');

        if (!$media) {
            return $placeholder;
        }

        return file_exists($media->getPath())
            ? $media->getUrl()
            : $placeholder;
    }

    public function getImageUrlsAttribute()
    {
        $placeholder = asset('/website/images/placeholder-lg.png');
        $smallPlaceholder = asset('/website/images/placeholder-sm.png');
        $media = $this->getFirstMedia('default');

        if (!$media) {
            return [
                'id' => null,
                'name' => null,
                'default' => $placeholder,
                'thumbnail' => $smallPlaceholder,
            ];
        }

        $path = $media->getPath();
        $exists = file_exists($path);

        return [
            'id' => $media->id,
            'name' => $media->name,
            'default' => $exists ? $media->getUrl() : $placeholder,
            'thumbnail' => $exists && $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $smallPlaceholder,
        ];
    }
}
