<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apiProductController;
use App\Http\Controllers\apiProductVariantController;
use App\Http\Controllers\apiCategoryController;
use App\Http\Controllers\apiProductReviewController;
use App\Http\Controllers\apiNotificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\FlashSaleController;
use App\Http\Controllers\apiWishlistController;
use App\Http\Controllers\apiBrandController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\API\OrderItemController;
use App\Http\Controllers\Admin\AdminOrdersController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminProductsController;
use App\Http\Controllers\FlashSaleProductController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\VNPayController;
use App\Http\Controllers\ai\BoxChatAIController;
use App\Http\Controllers\Admin\StatisticsController;

// Auth Routes
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
    Route::post('/updatecontactinfo', [AuthController::class, 'updateContactInfo'])->middleware('auth:api');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
});

// Wishlist Routes
Route::post('/favorites', [apiWishlistController::class, 'store']); // Thêm sản phẩm yêu thích
Route::get('/favorites', [apiWishlistController::class, 'index']);  // Lấy danh sách yêu thích của người dùng
Route::delete('/favorites/{id}', [apiWishlistController::class, 'destroy']);

// User Info Route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::get('/products/{productId}/variants', [AdminProductsController::class, 'getProductVariants']);
    Route::get('/products/lowest-stock', [AdminProductsController::class, 'getProductsWithLowestStock']);
    Route::get('/products/stock-quantity/{minStock}/{maxStock}', [AdminProductsController::class, 'getProductsByStockQuantity']);
    Route::put('/products/{productId}/variants/{variantId}/stock-quantity/{newStockQuantity}', [AdminProductsController::class, 'updateStockQuantity']);
    Route::put('/products/hot-status', [apiProductController::class, 'updateMultipleHotStatus']);
    Route::put('/products/remove-hot-status', [apiProductController::class, 'removeMultipleHotStatus']);
});

// Admin Orders Routes
Route::prefix('admin')->group(function () {
    Route::get('/orders', [AdminOrdersController::class, 'index']);
    Route::get('/orders/{pageSize}/{page}', [AdminOrdersController::class, 'show']);    
    Route::put('/orders/trang-thai/update-multiple', [AdminOrdersController::class, 'updateMultiple']);
});

// Product Routes
Route::prefix('products')->group(function () {
    Route::get('/sort', [apiProductController::class, 'sortByPrice']);
    Route::get('/', [apiProductController::class, 'index']);
    Route::get('/latest', [apiProductController::class, 'getLatestProducts']);

    Route::get('/hot', [apiProductController::class, 'getHotProducts']);

    Route::get('/best-selling', [apiProductController::class, 'getBestSellingProducts']);
    Route::get('/products_paginate', [apiProductController::class, 'products_paginate']);
    Route::get('/{id}', [apiProductController::class, 'show']);
    Route::post('/', [apiProductController::class, 'store']);
    Route::put('/{id}', [apiProductController::class, 'update']);
    Route::delete('/{id}', [apiProductController::class, 'delete']);
    Route::get('/search/{query}', [apiProductController::class, 'search']);
    Route::get('/{id}/related', [apiProductController::class, 'relatedProducts']);
    Route::get('/category/{categoryId}', [apiProductController::class, 'getProductsByCategory']);
    Route::get('/category/url/{categoryUrl}', [apiProductController::class, 'getProductsByCategoryUrl']);

    Route::get('/brand/{brandName}', [apiProductController::class, 'getProductsByBrand']);

});

// Variant Routes
Route::prefix('variants')->group(function () {
    Route::get('/', [apiProductVariantController::class, 'index']);
    Route::get('/product_id={product_id}', [apiProductVariantController::class, 'getProductsByProductId']);
    Route::get('/product_id={product_id}/variant_id={id}', [apiProductVariantController::class, 'getVariantByProductIdAndVariantId']);
    Route::get('/{id}', [apiProductVariantController::class, 'show']);
    Route::post('/', [apiProductVariantController::class, 'store']);
    Route::put('/{id}', [apiProductVariantController::class, 'update']);
    Route::delete('/{id}', [apiProductVariantController::class, 'delete']);
});

// Category Routes
Route::get('/categories', [apiCategoryController::class, 'index']);
Route::get('/categories/{id}', [apiCategoryController::class, 'show']);
Route::post('/categories', [apiCategoryController::class, 'store']);
Route::put('/categories/{id}', [apiCategoryController::class, 'update']);
Route::delete('/categories/{id}', [apiCategoryController::class, 'destroy']);
Route::get('/categorie/subcategories', [apiCategoryController::class, 'getSubcategories']);
Route::get('/categorie/parent-categories', [apiCategoryController::class, 'getParentCategories']);

// Product Review
Route::post('/product-reviews', [apiProductReviewController::class, 'store']);
Route::get('/products/{productId}/reviews', [apiProductReviewController::class, 'index']);
Route::get('/product-reviews', [apiProductReviewController::class, 'showProduct_reviewforUsser']);
Route::get('/admin/product-reviews', [apiProductReviewController::class, 'showProduct_reviewforAdmin']);
Route::post('/product-reviews/{id}/hide', [apiProductReviewController::class, 'hide']);
Route::delete('/product-reviews/{id}', [apiProductReviewController::class, 'destroy']);

// Notifications
Route::post('/notifications', [apiNotificationController::class, 'createNotification']);
Route::get('/notifications', [apiNotificationController::class, 'getUserNotifications']);
Route::get('/admin/notifications', [apiNotificationController::class, 'showAllNotifications']);
Route::put('/notifications/{id}/read', [apiNotificationController::class, 'markAsRead']);
Route::delete('/notifications/{id}', [apiNotificationController::class, 'destroy']);

// Promotion Routes
Route::get('/promotion', [PromotionController::class, 'index']);
Route::post('/promotion/create', [PromotionController::class, 'create']);
Route::post('/promotion/check', [PromotionController::class, 'check']);
Route::put('/promotion/{id}', [PromotionController::class, 'update']);
Route::delete('/promotion/{id}', [PromotionController::class, 'destroy']);

// Brand Routes
Route::get('/brands', [apiBrandController::class, 'index']);
Route::get('/brands/{id}', [apiBrandController::class, 'show']);
Route::post('/brands', [apiBrandController::class, 'store']);
Route::put('/brands/{id}', [apiBrandController::class, 'update']);
Route::delete('/brands/{id}', [apiBrandController::class, 'destroy']);

// Order Routes
Route::get('orders', [OrderController::class, 'index']);
Route::get('orders/{order_id}', [OrderController::class, 'showOrder']);
Route::post('orders', [OrderController::class, 'store']);
Route::get('order-items/{orderId}', [OrderItemController::class, 'showOrderItems']);
Route::get('pending-orders', [OrderController::class, 'showPendingOrder']);
Route::put('orders/{order_id}', [OrderController::class, 'update']);
Route::delete('orders/{order_id}', [OrderController::class, 'destroy']);
Route::get('orders/{order_id}/check-shipping', [OrderController::class, 'checkShippingInfo']);
Route::get('orders/shipping/list-orders-without-shipping', [OrderController::class, 'listOrdersWithoutShipping']);
Route::get('orders/shipping/list-orders-with-shipping', [OrderController::class, 'listOrdersWithShipping']);

// Flash Sale Routes
Route::get('flash-sales', [FlashSaleController::class, 'index']);  
Route::get('flash-sales/{id}', [FlashSaleController::class, 'show']);  
Route::post('flash-sales/create', [FlashSaleController::class, 'store']);  
Route::post('flash-sales/add-product', [FlashSaleProductController::class, 'addProductToFlashSale']);

// User Routes
Route::get('/users', [AdminUserController::class, 'index']);
Route::get('/users/search', [AdminUserController::class, 'searchUserByName']);
Route::get('/users/{id}', [AdminUserController::class, 'show']);
Route::post('/users', [AdminUserController::class, 'store']);
Route::put('/users/status/{id}', [AdminUserController::class, 'updateStatus']);
Route::get('/users/{userId}/orders', [AdminUserController::class, 'getOrdersByUser']);

// Shipping Routes
Route::get('/shipping/{order_id}', [ShippingController::class, 'show']);
Route::post('orders/{order_id}/shipping', [ShippingController::class, 'store']);

// Price Route
Route::get('product/{id}/price', [apiProductController::class, 'getProductPrice']);

// Payment Routes
Route::get('payment/{order_id}', [PaymentController::class, 'getPaymentInfo']);
Route::get('payment/transaction/{transaction_code}', [PaymentController::class, 'getLatestTransaction']);
Route::post('/create-payment', [VNPayController::class, 'createPayment']);
Route::get('/payment-return', [VNPayController::class, 'paymentReturn']);
Route::post('/update-payment-status', [VNPayController::class, 'updatePaymentStatus']);


// AI Routes
Route::post('/ai/search-product', [BoxChatAIController::class, 'searchProduct']);



// thông kê 
Route::get('/statistics/monthly/{year}', [StatisticsController::class, 'getMonthlyStatistics']);
Route::get('/statistics/daily/{monthYear}', [StatisticsController::class, 'getDailyStatistics']);

Route::get('/statistics/total-users', [StatisticsController::class, 'getTotalUsers']);
Route::get('/statistics/total-orders', [StatisticsController::class, 'getTotalOrders']);
Route::get('/statistics/total-products', [StatisticsController::class, 'getTotalProducts']);

Route::get('/statistics/orders-by-status', [StatisticsController::class, 'getOrdersByStatus']);

