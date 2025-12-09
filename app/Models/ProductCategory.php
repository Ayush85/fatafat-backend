<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_categories';

    protected $fillable = [
        'title', 'slug', 'description', 'parent_id', 'status',
        'meta_title', 'meta_keywords', 'meta_description',
        'parent_tree', 'featured', 'order', 'custom_code'
    ];

    protected $appends = ['category_full_name'];

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

    public function images()
    {
        return $this->hasMany(CategoryImage::class, 'product_category_id');
    }

    public function primaryImage()
    {
        return $this->hasOne(CategoryImage::class, 'product_category_id')->where('is_primary', true);
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
}
