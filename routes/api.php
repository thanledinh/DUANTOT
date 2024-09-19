<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apiProductController;
use App\Http\Controllers\apiProductVariantController;
use App\Http\Controllers\apiCategoryController;
use App\Http\Controllers\AuthController;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function () {
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::post('/profile', [AuthController::class, 'profile'])->middleware('auth:api');
    
    // Route cho việc quên mật khẩu
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    
    // Route cho việc đặt lại mật khẩu
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Nhóm các route liên quan đến products
Route::prefix('products')->group(function () {
    Route::get('/', [apiProductController::class, 'index']);
    Route::get('/{id}', [apiProductController::class, 'show']);
    Route::post('/', [apiProductController::class, 'store']);
    Route::put('/{id}', [apiProductController::class, 'update']);
    Route::delete('/{id}', [apiProductController::class, 'delete']);
    Route::get('/search/{query}', [apiProductController::class, 'search']);



    // Nhóm các route liên quan đến variants của product
    Route::prefix('variants')->group(function () {
        Route::get('/', [apiProductVariantController::class, 'index']);
        Route::get('/product_id={product_id}', [apiProductVariantController::class, 'getProductsByProductId']);
        Route::get('/product_id={product_id}/variant_id={id}', [apiProductVariantController::class, 'getVariantByProductIdAndVariantId']);
        Route::get('/{id}', [apiProductVariantController::class, 'show']);
        Route::post('/', [apiProductVariantController::class, 'store']);
        Route::put('/{id}', [apiProductVariantController::class, 'update']);
        Route::delete('/{id}', [apiProductVariantController::class, 'delete']);
    });
});

Route::get('/categories', [apiCategoryController::class, 'index']);
Route::get('/categories/{id}', [apiCategoryController::class, 'show']);
Route::post('/categories', [apiCategoryController::class, 'store']);
Route::put('/categories/{id}', [apiCategoryController::class, 'update']);
Route::delete('/categories/{id}', [apiCategoryController::class, 'destroy']);
