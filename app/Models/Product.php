<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const STATUS_DRAFT = 2;

    protected $fillable = [
        'name', 'slug', 'short_description', 'description', 'sku', 'price', 
        'original_price', 'brand_id', 'vendor_id', 'quantity', 'unit', 'weight',
        'length', 'width', 'height', 'status', 'is_featured', 'attributes',
        'attribute_class_id', 'variant_attributes', 'meta_title', 'meta_keywords',
        'meta_description', 'highlights', 'product_video_url', 'emi_enabled',
        'pre_order', 'pre_order_price', 'custom_code', 'warranty_description',
    ];

    protected $casts = [
        'attributes' => 'array',
        'variant_attributes' => 'array',
    ];

    protected $appends = ['average_rating', 'discounted_price'];

    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'categories_products', 'product_id', 'product_category_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order', 'asc');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
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
}
