<?php

use App\Http\Controllers\API\v1\AuthController;
use App\Http\Controllers\API\v1\Blog\BlogCategoryController;
use App\Http\Controllers\API\v1\Blog\BlogController;
use App\Http\Controllers\API\v1\Brand\BrandController;
use App\Http\Controllers\API\v1\Campaign\CampaignController;
use App\Http\Controllers\API\v1\CartController;
use App\Http\Controllers\API\v1\EMI\EmiRequestStoreController;
use App\Http\Controllers\API\v1\EmiBank\EmiBankController;
use App\Http\Controllers\API\v1\EmiRequestController;
use App\Http\Controllers\API\v1\OrderController;
use App\Http\Controllers\API\v1\OrderStoreController;
use App\Http\Controllers\API\v1\Product\CategoryController;
use App\Http\Controllers\API\v1\Product\ProductController;
use App\Http\Controllers\API\v1\ReviewController;
use App\Http\Controllers\API\v1\UserShippingAddressController;
use App\Http\Controllers\API\v1\Webstory\WebstoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/categorys/navbarItems', [CategoryController::class, 'navbarItems']);

Route::prefix('v1')->middleware('api.key')->group(function () {
    // Banners
    // Banners
    Route::get('banners', [\App\Http\Controllers\API\v1\BannerController::class, 'index'])->name('banner.list');
    Route::get('banners/{slug}', [\App\Http\Controllers\API\v1\BannerController::class, 'showBySlug'])->name('banner.detail');
    // Pages
    Route::get('pages/{slug}', [\App\Http\Controllers\API\v1\PageController::class, 'show']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index'])
        ->name('categories.index')
        ->defaults('description', 'Retrieve a list of product categories');

    Route::get('categories/parents', [CategoryController::class, 'parentCategories'])
        ->name('categories.parents')
        ->defaults('description', 'Retrieve a list of parent product categories');

    Route::get('categories/{id}', [CategoryController::class, 'show'])
        ->name('category.by.id')
        ->where('id', '[0-9]+')
        ->defaults('description', 'Get details of a specific category by ID');

    Route::get('categories/{slug}', [CategoryController::class, 'showBySlug'])
        ->name('category.by.slug')
        ->defaults('description', 'Get details of a specific category by SLUG');

    Route::get('categories/{slug}/products', [ProductController::class, 'categoryProducts'])
        ->name('category.products.by.slug')
        ->defaults('description', "Get products by category ID.\n\nSupports all parameters from /products endpoint:\n- `per_page`: Pagination\n- `sort`: Sorting\n- `include`: Relationships (brand, vendor, etc.)\n\n**Examples:**\n```\n# Default\nGET /api/v1/categories/1/products\n\n# Sorted by price low to high\nGET /api/v1/categories/1/products?sort=price_asc&per_page=20\n\n# With sorting and relationships\nGET /api/v1/categories/1/products?sort=newest&include=brand\n```");

    // Products
    Route::get('products', [ProductController::class, 'index'])
        ->name('product.list')
        ->defaults('description', "Retrieve a list of products.\n\n**Query Parameters:**\n- `search`: Search by product name, description, highlights (optional)\n- `name`: Alias for search parameter (optional)\n- `category_id`: Filter by category ID (optional)\n- `brand_id`: Filter by brand ID (optional)\n- `min_price`: Filter by minimum price (optional)\n- `max_price`: Filter by maximum price (optional)\n- `is_featured`: Filter featured products (optional)\n- `sort`: Sort order - price_asc, price_desc, name_asc, name_desc, newest (optional)\n- `per_page`: Number of products per page, default 10 (optional)\n- `include`: Load additional relationships - brand,categories,vendor,variants (optional, comma-separated)\n\n**Examples:**\n```\n# Fast - Media only (default)\nGET /api/v1/products?per_page=10\n\n# Sorting and Pagination\nGET /api/v1/products?per_page=20&sort=price_asc\nGET /api/v1/products?per_page=15&sort=newest\n\n# With brand and categories\nGET /api/v1/products?include=brand,categories\n\n# Full data with all relationships\nGET /api/v1/products?include=brand,categories,vendor,variants\n```");

    // Route::get('products/search', [ProductController::class, 'search'])
    //     ->name('products.search')
    //     ->defaults('description', "Search for products (same as products endpoint).\n\nSupports all parameters from /products endpoint including:\n- `search` or `name`: Search term\n- `category_id`, `brand_id`: Filters\n- `min_price`, `max_price`: Price range\n- `sort`: Sorting\n- `per_page`: Pagination\n- `include`: Additional relationships");

    // Route::get('products/{id}', [ProductController::class, 'show'])
    //     ->name('products.show')
    //     ->where('id', '[0-9]+')
    //     ->defaults('description', 'Get details of a specific product by ID');

    Route::get('products/{slug}', [ProductController::class, 'showBySlug'])
        ->name('product.detail')
        ->defaults('description', 'Get details of a specific product by SLUG');

    // Route::get('product-detail/{slug}', [ProductController::class, 'productDetail'])
    //     ->name('products.detail')
    //     ->defaults('description', 'Get details of a specific product by SLUG (Legacy endpoint)');

    // Authentication
    Route::post('register', [AuthController::class, 'register'])
        ->name('auth.register')
        ->defaults('description', "Register a new user account.\n\n**Required Fields:**\n- `name`: Full name (string, max 255)\n- `email`: Email address (string, must be unique)\n- `password`: Password (string, min 8 characters)\n- `password_confirmation`: Password confirmation (must match password)\n\n**Optional Fields:**\n- `contact_number`: Phone number (string, max 20)\n\n**Example:**\n```json\n{\n  \"name\": \"John Doe\",\n  \"email\": \"john@example.com\",\n  \"password\": \"password123\",\n  \"password_confirmation\": \"password123\",\n  \"contact_number\": \"9841234567\"\n}\n```");

    // Blog Categories
    Route::get('blogs/categories', [BlogCategoryController::class, 'index'])
        ->name('api.blogs.categories')
        ->defaults('description', 'Retrieve a list of blog categories');

    // Blogs
    Route::get('blogs', [BlogController::class, 'index'])
        ->name('api.blogs.index')
        ->defaults('description', "Retrieve a list of blogs\n\n**Filters:**\n- `category`: Filter by category slug or name\n- `category_id`: Filter by category ID\n- `author`: Filter by author name (partial match)\n- `created_at`: Filter by date (YYYY-MM-DD)\n- `featured`: Filter by featured status\n- `ordering`: Sort by created_at (e.g. 'created_at desc', 'crated_at acceding')");

    Route::get('blogs/{slug}', [BlogController::class, 'show'])
        ->name('api.blogs.show')
        ->defaults('description', 'Get details of a specific blog');

    // Product Brands
    Route::get('/brands', [BrandController::class, 'index'])
        ->name('brands.index');
    Route::get('/brands/{slug}', [BrandController::class, 'showBySlug'])
        ->name('brands.show')
        ->defaults('description', "Get product brand details by slug with related products.\n\n**Filters:**\n- `per_page`: Number of related products per page\n- `sort`: Sort related products by name (`asc` or `desc`)\n- `emi_enabled`: Filter related products by EMI support");

    // FAQs
    Route::get('faqs', [\App\Http\Controllers\API\v1\FaqController::class, 'index'])
        ->name('api.faqs.index')
        ->defaults('description', "Retrieve a list of FAQs.\n\n**Filters:**\n- `type`: Filter by FAQ type (optional)\n- `type_id`: Filter by type ID (optional)\n\n**Response:**\n- `success`: boolean\n- `data`: Array of FAQ objects\n- `message`: string");

    // Reviews
    Route::get('reviews/product/{id}', [ReviewController::class, 'getReviews'])
        ->name('reviews.product')
        ->defaults('description', 'Retrieve reviews for a product');

    // emi banks
    Route::get('emi-banks', [EmiBankController::class, 'emiBankList'])
        ->name('emi.bank.list');

    // campaigns
    Route::get('campaigns', [CampaignController::class, 'campaignList'])
        ->name('campaign.list');
    Route::get('campaigns/{slug}', [CampaignController::class, 'getCampaign'])
        ->name('campaign.detail');

    Route::get('webstories', [WebstoryController::class, 'getWebStories'])
        ->name('campaign.detail');

    // User Shipping Addresses - PROTECTED
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('shipping-addresses', [UserShippingAddressController::class, 'index'])
            ->name('shipping-addresses.index')
            ->defaults('description', 'Get all shipping addresses for the authenticated user');

        Route::post('shipping-addresses', [UserShippingAddressController::class, 'store'])
            ->name('shipping-addresses.store')
            ->defaults('description', "Create a new shipping address.\n\n**Required Fields:**\n- `full_name`: string\n- `phone`: string\n- `email`: email\n- `address`: string\n- `city`: string\n- `state`: string\n\n**Optional:**\n- `is_default`: boolean");

        Route::get('shipping-addresses/{id}', [UserShippingAddressController::class, 'show'])
            ->name('shipping-addresses.show');

        Route::put('shipping-addresses/{id}', [UserShippingAddressController::class, 'update'])
            ->name('shipping-addresses.update')
            ->defaults('description', 'Update a shipping address');

        Route::delete('shipping-addresses/{id}', [UserShippingAddressController::class, 'destroy'])
            ->name('shipping-addresses.destroy')
            ->defaults('description', 'Delete a shipping address');
    });

    // EMI Requests - MOVED to auth:sanctum group below
    // Route::post('emi-requests', [EmiRequestController::class, 'store'])
    //     ->name('emi-requests.store');

    // Wishlist
    Route::get('wishlist', [\App\Http\Controllers\API\v1\WishlistController::class, 'index'])
        ->name('wishlist.index')
        ->defaults('description', 'Get authenticated users wishlist');
    Route::post('wishlist', [\App\Http\Controllers\API\v1\WishlistController::class, 'store'])
        ->name('wishlist.store')
        ->defaults('description', "Add product to wishlist.\n\n**Required Fields:**\n- `product_id`: integer");
    Route::delete('wishlist/{productId}', [\App\Http\Controllers\API\v1\WishlistController::class, 'destroy'])
        ->name('wishlist.destroy')
        ->defaults('description', 'Remove product from wishlist');
});

// Legacy routes
Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/register', [AuthController::class, 'register'])
        ->defaults('description', "Register a new user account.\n\n**Required Fields:**\n- `name`: string\n- `email`: email\n- `password`: string (min 8)\n- `password_confirmation`: string\n- `contact_number`: string (optional)");

    Route::post('/login', [AuthController::class, 'login'])->name('login')
        ->defaults('description', "Login user.\n\n**Required:**\n- `email`\n- `password`");

    // Password reset & OTP
    Route::post('/forgottenpassword', [AuthController::class, 'forgottenPassword'])
        ->defaults('description', "Request OTP for password reset.\n\n**Required:**\n- `email`");

    Route::post('/otp/verify', [AuthController::class, 'verifyOTP'])
        ->defaults('description', "Verify OTP.\n\n**Required:**\n- `email`\n- `code`");

    Route::post('/password/reset', [AuthController::class, 'resetPassword'])
        ->defaults('description', "Reset password.\n\n**Required:**\n- `email`\n- `code`\n- `password`\n- `password_confirmation`");

    // Social login
    Route::post('/login/google', [AuthController::class, 'googleLogin'])
        ->defaults('description', "Google Login.\n\n**Required:**\n- `google_token`");

    Route::post('/login/facebook', [AuthController::class, 'facebookLogin'])
        ->defaults('description', "Facebook Login.\n\n**Required:**\n- `facebook_token`");

    // Reviews (public read)
    Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/profile-update', [AuthController::class, 'updateProfile'])
            ->defaults('description', "Update authentication user profile.\n\n**Required Fields:**\n- `name`: string\n- `email`: email\n\n**Optional Fields:**\n- `contact_number`: string\n- `date_of_birth`: date (YYYY-MM-DD)\n- `address`: string\n- `institute_name`: string\n- `photo`: image file (max 2MB)\n");
        Route::post('/refresh-token', [AuthController::class, 'refreshAccessToken']);

        // Cart
        Route::get('/cart', [CartController::class, 'index'])
            ->defaults('description', 'Get current user cart');
        Route::post('/cart/items', [CartController::class, 'addItem'])
            ->defaults('description', "Add item to cart.\n\n**Required:**\n- `product_id`\n- `quantity` (min 1)");
        Route::put('/cart/items/{itemId}', [CartController::class, 'updateItem'])
            ->defaults('description', "Update cart item quantity.\n\n**Required:**\n- `quantity`");
        Route::delete('/cart/items/{itemId}', [CartController::class, 'removeItem'])
            ->defaults('description', 'Remove item from cart');
        Route::delete('/cart/clear', [CartController::class, 'clear'])
            ->defaults('description', 'Clear entire cart');
        Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon'])
            ->defaults('description', "Apply coupon.\n\n**Required:**\n- `code`");
        Route::post('/cart/remove-coupon', [CartController::class, 'removeCoupon'])
            ->defaults('description', 'Remove applied coupon');

        // Orders
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');
        Route::post('/orders', [OrderStoreController::class, 'store'])
            ->name('orders.store');
        Route::get('/orders/{id}', [OrderController::class, 'show'])
            ->name('orders.show');
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])
            ->name('orders.cancel');

        // Reviews (write)
        Route::post('/products/{productId}/reviews', [ReviewController::class, 'store'])
            ->defaults('description', "Submit review.\n\n**Required:**\n- `rating` (1-5)\n- `review` (max 1000 chars)");

        // User Shipping Addresses
        Route::get('shipping-addresses', [UserShippingAddressController::class, 'index'])
            ->name('shipping-addresses.legacy.index')
            ->defaults('description', 'Get all shipping addresses for the authenticated user');

        Route::post('shipping-addresses', [UserShippingAddressController::class, 'store'])
            ->name('shipping-addresses.legacy.store')
            ->defaults('description', "Create a new shipping address.\n\n**Required Fields:**\n- `full_name`: string\n- `phone`: string\n- `email`: email\n- `address`: string\n- `city`: string\n- `state`: string\n\n**Optional:**\n- `is_default`: boolean");

        Route::get('shipping-addresses/{id}', [UserShippingAddressController::class, 'show'])
            ->name('shipping-addresses.legacy.show');

        Route::put('shipping-addresses/{id}', [UserShippingAddressController::class, 'update'])
            ->name('shipping-addresses.legacy.update')
            ->defaults('description', 'Update a shipping address');

        Route::delete('shipping-addresses/{id}', [UserShippingAddressController::class, 'destroy'])
            ->name('shipping-addresses.legacy.destroy')
            ->defaults('description', 'Delete a shipping address');

        // EMI Requests
        // Route::post('emi-requests', [EmiRequestController::class, 'store'])
        //     ->name('emi-requests.legacy.store')
        //     ->defaults('description', "Submit an EMI Request.\n\n**Required Fields:**\n- `name`, `email`, `contact_number`\n- `product_id`, `finance_amount`, `monthly_income`\n- `address`\n\n**File Uploads** (multipart/form-data):\n- `salary_certificate`\n- `citizenship`\n- `photo`\n- `bank_statement`");
        Route::post('emi-requests', [EmiRequestStoreController::class, 'store'])
            ->name('emi-requests.store')
            ->defaults('description', "Submit an EMI Request.\n\n**Required Fields:**\n- `name`, `email`, `contact_number`\n- `product_id`, `finance_amount`, `monthly_income`\n- `address`\n\n**File Uploads** (multipart/form-data):\n- `salary_certificate`\n- `citizenship`\n- `photo`\n- `bank_statement`");

        // Route::post('emi-request-with-credit-card', [EmiRequestStoreController::class, 'withCreditCard'])
        //     ->name('emi-requests.legacy.store');

        // Payment Gateways
        Route::prefix('payment')->group(function () {
            Route::post('nicasia/initiate', [\App\Http\Controllers\API\v1\Payment\NicAsiaController::class, 'initiatePayment'])
                ->defaults('description', "Initiate NIC Asia (CyberSource) Payment.\n\n**Required Fields:**\n- `order_id`: integer (ID of the order to pay for)\n\n**Response:**\nReturns a `payment_url` and a set of `params`. You must construct a hidden HTML form with these parameters and auto-submit it to the `payment_url` to redirect the user to the payment gateway.");

            Route::post('nicasia/verify', [\App\Http\Controllers\API\v1\Payment\NicAsiaController::class, 'verifyPayment'])
                ->defaults('description', "Verify NIC Asia Payment (Callback).\n\nThis endpoint is called by the payment gateway after the transaction. It verifies the signature and updates the order status.");

            Route::post('esewa/initiate', [\App\Http\Controllers\API\v1\Payment\EsewaController::class, 'initiatePayment'])
                ->defaults('description', "Initiate eSewa Payment.\n\n**Required Fields:**\n- `order_id`: integer\n\n**Response:**\nReturns `payment_url` and `params`. Construct a form with these parameters and submit to `payment_url`.");

            Route::post('esewa/verify', [\App\Http\Controllers\API\v1\Payment\EsewaController::class, 'verifyPayment'])
                ->defaults('description', "Verify eSewa Payment.\n\nUsed to verify payment after eSewa redirects back to the merchant site.");
        });
    });
});
