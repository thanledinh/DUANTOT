<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\apiProductController;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->group(function () {
    Route::get('/products', [AdminController::class, 'products'])->name('admin.products.index');
    Route::post('/products', [apiProductController::class, 'store']);
    Route::get('/products/create', [ProductController::class, 'create'])->name('admin.products.create');
});
