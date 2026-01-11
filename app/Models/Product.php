<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const STATUS_DRAFT = 2;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'sku',
        'price',
        'original_price',
        'brand_id',
        'vendor_id',
        'quantity',
        'unit',
        'weight',
        'length',
        'width',
        'height',
        'status',
        'is_featured',
        'attributes',
        'attribute_class_id',
        'variant_attributes',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'highlights',
        'product_video_url',
        'emi_enabled',
        'pre_order',
        'pre_order_price',
        'custom_code',
        'warranty_description',
    ];

    protected $casts = [
        'attributes' => 'array',
        'variant_attributes' => 'array',
    ];

    protected $appends = ['average_rating', 'discounted_price', 'default_media'];

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

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'categories_products', 'product_id', 'product_category_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function brand()
    {
        return $this->belongsTo(ProductBrand::class);
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class)->where('status', ProductReview::STATUS_APPROVED);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews->avg('rating') ?? 0;
    }

    public function getDiscountedPriceAttribute()
    {
        if (isset($this->discountcampaign) && $this->discountcampaign->campaign->is_active) {
            return $this->calculateDiscountedPrice();
        }
        return $this->price;
    }

    public function discountcampaign()
    {
        return $this->belongsTo(DiscountCampaignProduct::class, 'id', 'product_id')->with('campaign');
    }

    public function calculateDiscountedPrice()
    {
        $campaign = $this->discountcampaign;
        $product_price = $this->original_price ?: $this->price;

        if ($campaign->discount_type == 1) {
            $product_price -= $campaign->discount_value;
        } elseif ($campaign->discount_type == 2) {
            $product_price -= ($product_price * $campaign->discount_value / 100);
        }

        return $product_price;
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'like', "%{$keyword}%")
            ->orWhere('description', 'like', "%{$keyword}%");
    }

    public function scopeFilter($query, $filters)
    {
        foreach ($filters as $key => $filter) {
            if (count($filter)) {
                $query->whereIn("attributes->product_attributes->$key", $filter);
            }
        }
        return $query;
    }

    public function defaultMedia()
    {
        $default_media = $this->getMedia('default', function (Media $media) {
            return !isset($media->custom_properties['color']) && isset($media->custom_properties['is_default']) && $media->custom_properties['is_default'];
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
