<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\v1\AuthController;
use App\Http\Controllers\API\v1\ProductController;
use App\Http\Controllers\API\v1\CategoryController;
use App\Http\Controllers\API\v1\CartController;
use App\Http\Controllers\API\v1\OrderController;
use App\Http\Controllers\API\v1\ReviewController;
use App\Http\Controllers\API\v1\BlogController;

// Admin controllers - temporarily disabled
/*
use App\Http\Controllers\API\Admin\ApiKeyController;
use App\Http\Controllers\API\Admin\MenuController;
use App\Http\Controllers\API\Admin\PaymentMethodController;
use App\Http\Controllers\API\Admin\ProductCategoryController;
use App\Http\Controllers\API\Admin\ProductBrandController;
use App\Http\Controllers\API\Admin\ProductsController;
use App\Http\Controllers\API\Admin\VendorController;
use App\Http\Controllers\API\Admin\DashboardController;
use App\Http\Controllers\API\Admin\AdminUserController;
use App\Http\Controllers\API\Admin\UsersController;
use App\Http\Controllers\API\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\API\Admin\PagesController;
use App\Http\Controllers\API\Admin\BannerController;
use App\Http\Controllers\API\Admin\CouponDiscountController;
*/

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Admin routes - TEMPORARILY DISABLED
/*
Route::group(['prefix' => 'admin', 'middleware' => 'auth:apiAdmin'], function () {
    // API Keys
    Route::get('/apikeys', [ApiKeyController::class, 'index']);
    Route::post('/apikeys', [ApiKeyController::class, 'store']);
    Route::get('/apikeys/{id}', [ApiKeyController::class, 'show']);
    Route::patch('/apikeys/{id}/status', [ApiKeyController::class, 'toggleStatus']);
    Route::patch('/apikeys/{id}/mode', [ApiKeyController::class, 'toggleMode']);

    // Menus
    Route::resource('menus', MenuController::class)->except(['create', 'edit']);

    // Payment Methods
    Route::resource('payment-methods', PaymentMethodController::class);

    // Product Categories
    Route::post('product-categories/sort-categories', [ProductCategoryController::class, 'sortCategories']);
    Route::get('/product-categories/{id}/images', [ProductCategoryController::class, 'getProductCategoryImages']);
    Route::post('/product-categories/{id}/images', [ProductCategoryController::class, 'saveProductCategoryImage']);
    Route::delete('/product-categories/{id}/images/{image_id}', [ProductCategoryController::class, 'deleteProductCategoryImage']);
    Route::resource('product-categories', ProductCategoryController::class)->except(['create', 'edit']);
    Route::get('product-categories-tree', [ProductCategoryController::class, 'getCategoryTree']);
    Route::get('product-categories-dropdown', [ProductCategoryController::class, 'getCategoryDropdown']);

    // Product Brands
    Route::get('/product-brands/get-dropdown', [ProductBrandController::class, 'getBrandsDropdown']);
    Route::get('/product-brands/{id}/images', [ProductBrandController::class, 'getProductBrandImages']);
    Route::post('/product-brands/{id}/images', [ProductBrandController::class, 'saveProductBrandImage']);
    Route::delete('/product-brands/{id}/images/{image_id}', [ProductBrandController::class, 'deleteProductBrandImage']);
    Route::resource('product-brands', ProductBrandController::class)->except(['create', 'edit']);

    // Vendors
    Route::get('/vendors/get-dropdown', [VendorController::class, 'getVendorsDropdown']);
    Route::get('/vendors/{id}/products', [ProductsController::class, 'getVendorsProducts']);
    Route::resource('vendors', VendorController::class)->except(['create', 'edit']);

    // Products
    Route::post('products/store-detail', [ProductsController::class, 'storeProductDetail']);
    Route::patch('products/{id}/update-detail', [ProductsController::class, 'updateProductDetail']);
    Route::patch('products/{id}/update-meta', [ProductsController::class, 'updateMetaFields']);
    Route::post('product/make-duplicate', [ProductsController::class, 'duplicateProduct']);
    Route::patch('products/{id}/update-price', [ProductsController::class, 'updateProductPrice']);
    Route::patch('products/{id}/save-attributes', [ProductsController::class, 'saveProductAttributes']);
    Route::patch('products/{id}/toggle-status', [ProductsController::class, 'toggleProductStatus']);
    Route::post('products/{id}/uploadImages', [ProductsController::class, 'uploadProductImage']);
    Route::get('products/{id}/get-product-images', [ProductsController::class, 'getProductImages']);
    Route::delete('products/product-image/{image_id}/delete-image', [ProductsController::class, 'removeProductImages']);
    Route::post('products/{product_id}/product-image/{image_id}/set-primary', [ProductsController::class, 'setPrimaryImage']);
    Route::resource('products', ProductsController::class)->except(['create', 'edit', 'store']);

    // Dashboard
    Route::get('/dashboard-data', [DashboardController::class, 'getDashboardData']);

    // Admin Users
    Route::get('/admin-users/user-roles', [AdminUserController::class, 'getUserRoles']);
    Route::resource('admin-users', AdminUserController::class);

    // Users
    Route::resource('users', UsersController::class)->except(['show', 'create', 'edit']);
    Route::get('user-list', [UsersController::class, 'userList']);

    // Blogs
    Route::patch('blogs/{id}/toggle-status', [AdminBlogController::class, 'toggleBlogStatus']);
    Route::patch('blogs/{id}/toggle-feature', [AdminBlogController::class, 'toggleBlogFeature']);
    Route::resource('blogs', AdminBlogController::class)->except(['create', 'edit']);

    // Pages
    Route::resource('pages', PagesController::class)->except(['create', 'edit']);

    // Banners
    Route::resource('banners', BannerController::class)->except(['create', 'edit']);

    // Coupon Discounts
    Route::resource('coupon-discounts', CouponDiscountController::class)->except(['create', 'edit']);
});
*/

// Public API routes (no authentication required)
Route::get('/get-all-categories', [\App\Http\Controllers\API\v1\CategoryController::class, 'index']);

Route::get('/get-all-brands', [\App\Http\Controllers\API\v1\BrandController::class, 'index']);

Route::get('/get-all-products', [\App\Http\Controllers\API\v1\ProductController::class, 'index']);

Route::prefix('v1')->middleware('api.key')->group(function () {
    // Banners
    Route::get('banners', [\App\Http\Controllers\API\v1\BannerController::class, 'index']);
    // Pages
    Route::get('pages/{slug}', [\App\Http\Controllers\API\v1\PageController::class, 'show']);

    // Products
    Route::get('products', [ProductController::class, 'index'])
        ->name('products.index')
        ->defaults('description', "Retrieve a list of products.\n\n**Query Parameters:**\n- `search`: Search by product name, description, highlights (optional)\n- `name`: Alias for search parameter (optional)\n- `category_id`: Filter by category ID (optional)\n- `brand_id`: Filter by brand ID (optional)\n- `min_price`: Filter by minimum price (optional)\n- `max_price`: Filter by maximum price (optional)\n- `is_featured`: Filter featured products (optional)\n- `sort`: Sort order - price_asc, price_desc, name_asc, name_desc, newest (optional)\n- `per_page`: Number of products per page, default 10 (optional)\n- `include`: Load additional relationships - brand,categories,vendor,variants (optional, comma-separated)\n\n**Examples:**\n```\n# Fast - Media only (default)\nGET /api/v1/products?per_page=10\n\n# With brand\nGET /api/v1/products?include=brand\n\n# With brand and categories\nGET /api/v1/products?include=brand,categories\n\n# Full data\nGET /api/v1/products?include=brand,categories,vendor,variants\n```");

    Route::get('products/search', [ProductController::class, 'search'])
        ->name('products.search')
        ->defaults('description', "Search for products (same as products endpoint).\n\nSupports all parameters from /products endpoint including:\n- `search` or `name`: Search term\n- `category_id`, `brand_id`: Filters\n- `min_price`, `max_price`: Price range\n- `sort`: Sorting\n- `per_page`: Pagination\n- `include`: Additional relationships");

    Route::get('products/{id}', [ProductController::class, 'show'])
        ->name('products.show')
        ->defaults('description', 'Get details of a specific product by ID');

    Route::get('products/slug/{slug}', [ProductController::class, 'showBySlug'])
        ->name('products.slug')
        ->defaults('description', 'Get details of a specific product by SLUG');

    Route::get('product-detail/{slug}', [ProductController::class, 'productDetail'])
        ->name('products.detail')
        ->defaults('description', 'Get details of a specific product by SLUG (Legacy endpoint)');

    // Categories
    Route::get('categories', [CategoryController::class, 'index'])
        ->name('categories.index')
        ->defaults('description', 'Retrieve a list of product categories');

    Route::get('categories/parents', [CategoryController::class, 'parentCategories'])
        ->name('categories.parents')
        ->defaults('description', 'Retrieve a list of parent product categories');

    Route::get('categories/slug/{slug}', [CategoryController::class, 'showBySlug'])
        ->name('categories.slug')
        ->defaults('description', 'Get details of a specific category by SLUG');

    Route::get('categories/{id}', [CategoryController::class, 'show'])
        ->name('categories.show')
        ->defaults('description', 'Get details of a specific category by ID');

    // Blogs
    Route::get('blogs', [BlogController::class, 'index'])
        ->name('api.blogs.index')
        ->defaults('description', 'Retrieve a list of blogs');

    Route::get('blogs/{slug}', [BlogController::class, 'show'])
        ->name('api.blogs.show')
        ->defaults('description', 'Get details of a specific blog');

    // Reviews
    Route::get('reviews/product/{id}', [ReviewController::class, 'getReviews'])
        ->name('reviews.product')
        ->defaults('description', 'Retrieve reviews for a product');
});

// Legacy routes
Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Password reset & OTP
    Route::post('/forgottenpassword', [AuthController::class, 'forgottenPassword']);
    Route::post('/otp/verify', [AuthController::class, 'verifyOTP']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);

    // Social login
    Route::post('/login/google', [AuthController::class, 'googleLogin']);
    Route::post('/login/facebook', [AuthController::class, 'facebookLogin']);

    // Reviews (public read)
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh-token', [AuthController::class, 'refreshAccessToken']);

        // Cart
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/items', [CartController::class, 'addItem']);
        Route::put('/cart/items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('/cart/items/{itemId}', [CartController::class, 'removeItem']);
        Route::delete('/cart/clear', [CartController::class, 'clear']);
        Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon']);
        Route::post('/cart/remove-coupon', [CartController::class, 'removeCoupon']);

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        // Reviews (write)
        Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
    });
});
