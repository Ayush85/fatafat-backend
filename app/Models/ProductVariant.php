<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'price', 'original_price', 
        'quantity', 'attributes', 'status'
    ];

    protected $casts = [
        'attributes' => 'array',
    ];

    protected $appends = ['discounted_price'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->hasMany(VariantImage::class)->orderBy('display_order', 'asc');
    }

    public function primaryImage()
    {
        return $this->hasOne(VariantImage::class)->where('is_primary', true);
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
}
