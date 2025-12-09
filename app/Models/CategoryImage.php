<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'image_path',
        'image_url',
        'is_primary',
        'alt_text',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
