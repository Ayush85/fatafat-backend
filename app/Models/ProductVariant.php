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

    public function getDiscountedPriceAttribute()
    {
        return $this->price;
    }
}
