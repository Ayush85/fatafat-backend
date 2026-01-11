<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    /**
     * Map old package namespace to new App namespace
     */
    protected static $modelTypeMap = [
        'Jed\\Ecommerce\\App\\Product' => 'App\\Models\\Product',
        'Jed\\Ecommerce\\App\\ProductVariant' => 'App\\Models\\ProductVariant',
        'Jed\\Ecommerce\\App\\ProductCategory' => 'App\\Models\\ProductCategory',
        'Jed\\Ecommerce\\App\\ProductBrand' => 'App\\Models\\ProductBrand',
        'Jed\\Ecommerce\\App\\Blog' => 'App\\Models\\Blog',
        'Jed\\Ecommerce\\App\\BannerImage' => 'App\\Models\\BannerImage',
        'Jed\\Ecommerce\\App\\Page' => 'App\\Models\\Page',
    ];

    /**
     * Boot the model and map old namespaces
     */
    protected static function boot()
    {
        parent::boot();

        static::retrieved(function ($media) {
            if (isset(self::$modelTypeMap[$media->model_type])) {
                $media->model_type = self::$modelTypeMap[$media->model_type];
            }
        });
    }
}
