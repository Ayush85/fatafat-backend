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

// API Documentation
Route::get('/documentation', function () {
    return view('api-documentation');
})->name('api.documentation');

// Redirect /docs to /documentation
Route::get('/docs', function () {
    return redirect('/documentation');
});

// Serve OpenAPI spec at /documentation/openapi.yaml
Route::get('/documentation/openapi.yaml', function () {
    return response()->file(public_path('docs/openapi.yaml'));
});

// Serve Postman collection
Route::get('/documentation/collection.json', function () {
    return response()->file(public_path('docs/collection.json'));
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
