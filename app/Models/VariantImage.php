<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariantImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'image_path',
        'image_url',
        'is_primary',
        'alt_text',
        'display_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
