<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apiProductController;
use App\Http\Controllers\apiProductVariantController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('products', [apiProductController::class, 'index']);


Route::post('product', [apiProductController::class, 'store']);
Route::put('product/{id}', [apiProductController::class, 'update']);
Route::delete('product/{id}', [apiProductController::class, 'delete']);

Route::get('products/variants', [apiProductVariantController::class, 'index']);
Route::get('products/variants/product_id={product_id}', [apiProductVariantController::class, 'getProductsByProductId']);
Route::get('products/variants/product_id={product_id}/variant_id={id}', [apiProductVariantController::class, 'getVariantByProductIdAndVariantId']);
Route::get('product/variant/{id}', [apiProductVariantController::class, 'show']);

Route::post('product/variant', [apiProductVariantController::class, 'store']);
Route::put('product/variant/{id}', [apiProductVariantController::class, 'update']);
Route::delete('product/variant/{id}', [apiProductVariantController::class, 'delete']);