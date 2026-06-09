<?php

use App\Http\Controllers\API\v2\EMI\EmiRequestStoreController;
use App\Http\Controllers\API\v2\File\FileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v2')->group(function () {
    /** --------------------File Uploads----------------- */
    Route::post('upload/file', [FileController::class, 'uploadSingle']);
    Route::post('upload/files', [FileController::class, 'uploadBulk']);


     Route::middleware('auth:sanctum')->group(function () {
         Route::post('emi-requests', [EmiRequestStoreController::class, 'store'])
             ->name('v2.emi-requests.store');
     });
});
