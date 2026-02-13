<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// API Documentation - Custom Swagger UI with Payment Guide
Route::get('/documentation', function () {
    return view('api-documentation');
})->name('api.documentation');

// Redirect /docs to /documentation
Route::get('/docs', function () {
    return redirect('/documentation');
});

// Static Scribe HTML docs (alternative access)
Route::get('/docs/index.html', function () {
    return response()->file(public_path('docs/index.html'));
});

// Serve Postman collection (if generated)
Route::get('/documentation/collection.json', function () {
    $path = public_path('docs/collection.json');
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404);
});

// Serve OpenAPI spec
Route::get('/documentation/openapi.yaml', function () {
    $path = public_path('docs/openapi.yaml');
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404);
});

// Health check / Status endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});

// Redirect root to documentation
Route::get('/', function () {
    return redirect('/documentation');
});
