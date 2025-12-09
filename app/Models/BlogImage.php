<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'image_path',
        'image_url',
        'is_primary',
        'alt_text',
        'display_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
