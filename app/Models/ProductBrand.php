<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductBrand extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'status', 'meta_title', 
        'meta_keywords', 'meta_description'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
