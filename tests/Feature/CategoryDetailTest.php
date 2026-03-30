<?php

namespace Tests\Feature;

use App\Models\ProductBrandModel;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryDetailTest extends TestCase
{
    use DatabaseTransactions;

    public function test_category_detail_includes_price_range_and_brands()
    {
        if (! Schema::hasTable('product_categories') || ! Schema::hasTable('products') || ! Schema::hasTable('product_brands')) {
            $this->markTestSkipped('Required product tables are not available.');
        }

        $category = new ProductCategoryModel();
        $category->title = 'Test Category';
        $category->slug = 'test-category-' . uniqid();
        $category->status = 1;
        $category->save();

        $brandOne = new ProductBrandModel();
        $brandOne->name = 'Brand One';
        $brandOne->slug = 'brand-one-' . uniqid();
        $brandOne->status = 1;
        $brandOne->save();

        $brandTwo = new ProductBrandModel();
        $brandTwo->name = 'Brand Two';
        $brandTwo->slug = 'brand-two-' . uniqid();
        $brandTwo->status = 1;
        $brandTwo->save();

        $productLow = new ProductModel();
        $productLow->name = 'Product Low';
        $productLow->slug = 'product-low-' . uniqid();
        $productLow->price = 100;
        $productLow->status = ProductModel::STATUS_ENABLED;
        $productLow->brand_id = $brandOne->id;
        $productLow->save();

        $productHigh = new ProductModel();
        $productHigh->name = 'Product High';
        $productHigh->slug = 'product-high-' . uniqid();
        $productHigh->price = 300;
        $productHigh->status = ProductModel::STATUS_ENABLED;
        $productHigh->brand_id = $brandTwo->id;
        $productHigh->save();

        $productLow->categories()->syncWithoutDetaching([$category->id]);
        $productHigh->categories()->syncWithoutDetaching([$category->id]);

        $response = $this->getJson('/api/v1/categories/' . $category->slug);

        $response->assertStatus(200)
            ->assertJsonPath('data.price_range.min', 100)
            ->assertJsonPath('data.price_range.max', 300)
            ->assertJsonPath('data.brands.0.name', $brandOne->name);

        $brands = collect($response->json('data.brands'));
        $this->assertTrue($brands->pluck('slug')->contains($brandOne->slug));
        $this->assertTrue($brands->pluck('slug')->contains($brandTwo->slug));

        $brands->each(function ($brand) {
            $this->assertArrayHasKey('name', $brand);
            $this->assertArrayHasKey('slug', $brand);
            $this->assertArrayHasKey('thumb', $brand);
        });
    }
}
