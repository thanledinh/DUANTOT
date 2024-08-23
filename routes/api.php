<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apiProductController;
use App\Http\Controllers\apiProductVariantController;
use App\Http\Controllers\apiCategoryController;

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

// Nhóm các route liên quan đến categories
Route::apiResource('categories', apiCategoryController::class);
