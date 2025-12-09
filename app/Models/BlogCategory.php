<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'short_desc',
        'content',
        'status',
        'meta_title',
        'meta_keywords',
        'meta_description',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'category_id');
    }

    public function images()
    {
        return $this->hasMany(BlogImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(BlogImage::class)->where('is_primary', true);
    }
}
