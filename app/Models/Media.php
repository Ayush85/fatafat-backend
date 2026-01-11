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
     * Get the owning model.
     */
    public function model()
    {
        // Map old namespace to new namespace
        $modelType = $this->model_type;

        if (isset(self::$modelTypeMap[$modelType])) {
            $this->model_type = self::$modelTypeMap[$modelType];
        }

        return $this->morphTo();
    }

    /**
     * Override getAttribute to map model_type on the fly
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($key === 'model_type' && isset(self::$modelTypeMap[$value])) {
            return self::$modelTypeMap[$value];
        }

        return $value;
    }
}
