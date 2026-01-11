<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMediaModelTypes extends Command
{
    protected $signature = 'media:update-model-types';
    protected $description = 'Update media table model_type from old package namespace to new App namespace';

    public function handle()
    {
        $this->info('Updating media model types...');

        $mappings = [
            'Jed\\Ecommerce\\App\\Product' => 'App\\Models\\Product',
            'Jed\\Ecommerce\\App\\ProductVariant' => 'App\\Models\\ProductVariant',
            'Jed\\Ecommerce\\App\\ProductCategory' => 'App\\Models\\ProductCategory',
            'Jed\\Ecommerce\\App\\ProductBrand' => 'App\\Models\\ProductBrand',
            'Jed\\Ecommerce\\App\\Blog' => 'App\\Models\\Blog',
            'Jed\\Ecommerce\\App\\BannerImage' => 'App\\Models\\BannerImage',
            'Jed\\Ecommerce\\App\\Page' => 'App\\Models\\Page',
        ];

        $totalUpdated = 0;

        foreach ($mappings as $oldType => $newType) {
            $count = DB::table('media')
                ->where('model_type', $oldType)
                ->count();

            if ($count > 0) {
                DB::table('media')
                    ->where('model_type', $oldType)
                    ->update(['model_type' => $newType]);

                $this->info("Updated {$count} records from {$oldType} to {$newType}");
                $totalUpdated += $count;
            }
        }

        $this->info("Total records updated: {$totalUpdated}");
        $this->info('Done!');

        return 0;
    }
}
