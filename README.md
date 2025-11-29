# Fatafat API

A Laravel-based RESTful API for the Fatafat e-commerce platform.

## Features

- **Authentication**: Laravel Sanctum for API token authentication
- **Products**: Full product management with categories, brands, variants
- **Cart**: Shopping cart functionality
- **Orders**: Order placement and management
- **Reviews**: Product reviews and ratings
- **API Documentation**: Auto-generated with Scribe

## Installation

⚠️ **IMPORTANT:** You must run `composer install` BEFORE using any `php artisan` commands!

1. Install dependencies (REQUIRED FIRST):
```bash
cd fatafat-api
composer install
```

2. Environment file is already configured (`.env` exists with database credentials)

3. Generate application key:
```bash
php artisan key:generate
```

4. Configure your database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=fatafatnew
DB_USERNAME=root
DB_PASSWORD=your_password
```

5. Run migrations (database already exists):
```bash
php artisan migrate
```

6. Generate API documentation:
```bash
php artisan scribe:generate
```

7. Start the development server:
```bash
php artisan serve
```

## API Endpoints

### Authentication
- `POST /api/v1/register` - Register new user
- `POST /api/v1/login` - Login user
- `POST /api/v1/logout` - Logout user (authenticated)
- `GET /api/v1/me` - Get current user (authenticated)

### Products
- `GET /api/v1/products` - List all products
- `GET /api/v1/products/{id}` - Get product by ID
- `GET /api/v1/products/slug/{slug}` - Get product by slug

### Categories
- `GET /api/v1/categories` - List all categories
- `GET /api/v1/categories/{id}` - Get category by ID
- `GET /api/v1/categories/slug/{slug}` - Get category by slug

### Cart (Authenticated)
- `GET /api/v1/cart` - Get current cart
- `POST /api/v1/cart/items` - Add item to cart
- `PUT /api/v1/cart/items/{itemId}` - Update cart item
- `DELETE /api/v1/cart/items/{itemId}` - Remove cart item
- `DELETE /api/v1/cart/clear` - Clear cart

### Orders (Authenticated)
- `GET /api/v1/orders` - List user orders
- `POST /api/v1/orders` - Place new order
- `GET /api/v1/orders/{id}` - Get order details
- `POST /api/v1/orders/{id}/cancel` - Cancel order

### Reviews
- `GET /api/v1/products/{productId}/reviews` - Get product reviews
- `POST /api/v1/products/{productId}/reviews` - Add review (authenticated)

## Query Parameters

### Products
- `search` or `name` - Search products
- `category_id` - Filter by category
- `brand_id` - Filter by brand
- `min_price` - Minimum price
- `max_price` - Maximum price
- `is_featured` - Featured products only
- `sort` - Sort by: `price_asc`, `price_desc`, `name_asc`, `name_desc`, `newest`
- `per_page` - Items per page (default: 20)

### Categories
- `parent_id` - Filter by parent category
- `root` - Get root categories only
- `featured` - Featured categories only
- `with_children` - Include child categories
- `with_parent` - Include parent category
- `paginate=false` - Get all without pagination

## Authentication

Use Bearer token authentication:

```bash
Authorization: Bearer {your_token}
```

## Response Format

Success response:
```json
{
    "success": true,
    "message": "Success",
    "data": {}
}
```

Error response:
```json
{
    "success": false,
    "message": "Error message",
    "errors": {}
}
```

## Database

This API uses the existing `fatafatnew` database. No database changes are required.

## Documentation

After running `php artisan scribe:generate`, visit `/docs` for interactive API documentation.

## License

MIT
