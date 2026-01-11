<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class MorphMapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register morph map to handle old package namespace
        Relation::morphMap([
            'Jed\\Ecommerce\\App\\Product' => 'App\\Models\\Product',
            'Jed\\Ecommerce\\App\\ProductVariant' => 'App\\Models\\ProductVariant',
            'Jed\\Ecommerce\\App\\ProductCategory' => 'App\\Models\\ProductCategory',
            'Jed\\Ecommerce\\App\\ProductBrand' => 'App\\Models\\ProductBrand',
            'Jed\\Ecommerce\\App\\Blog' => 'App\\Models\\Blog',
            'Jed\\Ecommerce\\App\\BannerImage' => 'App\\Models\\BannerImage',
            'Jed\\Ecommerce\\App\\Page' => 'App\\Models\\Page',
        ]);
    }
}
