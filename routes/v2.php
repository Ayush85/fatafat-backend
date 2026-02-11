<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\v2\ShippingAddressController;
use App\Http\Controllers\API\v2\OrderController;
// use App\Http\Controllers\API\v2\ProductController;

/*
|--------------------------------------------------------------------------
| API v2 Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::middleware('auth:api')->group(function () {
    // Shipping Addresses
    Route::apiResource('shipping-addresses', ShippingAddressController::class);

    // Orders
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
});

// Products (Public)
Route::controller(App\Http\Controllers\API\v2\ProductController::class)->group(function () {
    Route::get('products', 'index');
    Route::get('products/{id}', 'show')->where('id', '[0-9]+');
    Route::get('products/{slug}', 'showBySlug')->where('slug', '[a-z0-9-]+');
});

// Categories (Public)
Route::controller(App\Http\Controllers\API\v2\ProductCategoryController::class)->group(function () {
    Route::get('categories', 'index');
    Route::get('categories/{slug}', 'show');
});

