<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apiProductController;
use App\Http\Controllers\apiProductVariantController;
use App\Http\Controllers\apiCategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\FlashSaleController;
use App\Http\Controllers\FlashSaleProductController;
use App\Http\Controllers\apiWishlistController;
use App\Http\Controllers\apiBrandController;

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
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('auth:api');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');


});


Route::middleware(['auth:api', 'custom_throttle:5,1'])->group(function () {
    Route::post('/favorites', [apiWishlistController::class, 'store']); // Thêm sản phẩm yêu thích
    Route::get('/favorites', [apiWishlistController::class, 'index']);  // Lấy danh sách yêu thích của người dùng
    Route::delete('/favorites/{id}', [apiWishlistController::class, 'destroy']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// Nhóm các route liên quan đến products
Route::prefix('products')->group(function () {
    Route::get('/sort', [apiProductController::class, 'sortByPrice']);
    Route::get('/', [apiProductController::class, 'index']);
    Route::get('/products_paginate', [apiProductController::class, 'products_paginate']);
    Route::get('/{id}', [apiProductController::class, 'show']);
    Route::post('/', [apiProductController::class, 'store']);
    Route::put('/{id}', [apiProductController::class, 'update']);
    Route::delete('/{id}', [apiProductController::class, 'delete']);
    Route::get('/search/{query}', [apiProductController::class, 'search']);
    Route::get('/{id}/related', [apiProductController::class, 'relatedProducts']);
});


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

Route::get('/categories', [apiCategoryController::class, 'index']);
Route::get('/categories/{id}', [apiCategoryController::class, 'show']);
Route::post('/categories', [apiCategoryController::class, 'store']);
Route::put('/categories/{id}', [apiCategoryController::class, 'update']);
Route::delete('/categories/{id}', [apiCategoryController::class, 'destroy']);

Route::post('/promotion/create', [PromotionController::class, 'create']);
Route::post('/promotion/check', [PromotionController::class, 'check']);
Route::post('/promotion/apply', [PromotionController::class, 'apply']);



Route::get('flash-sales', [FlashSaleController::class, 'index'])->name('flash-sales.index');
Route::get('flash-sales/{id}', [FlashSaleController::class, 'show'])->name('flash-sales.show');
Route::post('flash-sales', [FlashSaleController::class, 'store'])->name('flash-sales.store');
Route::put('flash-sales/{id}', [FlashSaleController::class, 'update'])->name('flash-sales.update');
Route::patch('flash-sales/{id}', [FlashSaleController::class, 'update'])->name('flash-sales.update');
Route::delete('flash-sales/{id}', [FlashSaleController::class, 'destroy'])->name('flash-sales.destroy');

Route::get('/brands', [apiBrandController::class, 'index']);
Route::get('/brands/{id}', [apiBrandController::class, 'show']);
Route::post('/brands', [apiBrandController::class, 'store']);
Route::put('/brands/{id}', [apiBrandController::class, 'update']);
Route::delete('/brands/{id}', [apiBrandController::class, 'destroy']);

