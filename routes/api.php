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
use App\Http\Controllers\UserController;
use App\Http\Controllers\BlogController;

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
Route::get('/favorites', [apiWishlistController::class, 'index']);  // Lấy danh sách yêu thích của người dùng
Route::post('/favorites', [apiWishlistController::class, 'store']); // Thêm sản phẩm yêu thích
Route::delete('/favorites/{id}', [apiWishlistController::class, 'destroy']);


// User Info Route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Admin Routes
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::get('/admin/products', [apiProductController::class, 'showWithoutHidden']);
    Route::get('/admin/variants', [apiProductVariantController::class, 'showWithoutHidden']);
    Route::get('/admin/products/{productId}/variants', [AdminProductsController::class, 'getProductVariants']);
    Route::get('/admin/products/lowest-stock', [AdminProductsController::class, 'getProductsWithLowestStock']);
    Route::get('/admin/products/variants/stock-quantity', [AdminProductsController::class, 'index']);
    Route::get('/admin/products/stock-quantity/{minStock}/{maxStock}', [AdminProductsController::class, 'getProductsByStockQuantity']);
    Route::put('/admin/products/{productId}/variants/{variantId}/stock-quantity/{newStockQuantity}', [AdminProductsController::class, 'updateStockQuantity']);
    Route::put('/admin/products/hot-status', [apiProductController::class, 'updateMultipleHotStatus']);
    Route::put('/admin/products/remove-hot-status', [apiProductController::class, 'removeMultipleHotStatus']);
    Route::put('/admin/products/update-stock-quantity/{productId}/{variantId}/{newStockQuantity}', [AdminProductsController::class, 'updateStockQuantity']);
    Route::get('/admin/products/search', [AdminProductsController::class, 'searchProducts']);
    Route::get('/admin/products/low-stock-alerts', [AdminProductsController::class, 'getLowStockAlerts']);
    Route::get('/admin/products/stock-history/{variantId}', [AdminProductsController::class, 'getStockHistory']);
});


// Admin Orders Routes
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::get('/admin/orders', [AdminOrdersController::class, 'index']);
    Route::get('/admin/orders/{pageSize}/{page}', [AdminOrdersController::class, 'show']);
    Route::put('/admin/orders/trang-thai/update-multiple', [AdminOrdersController::class, 'updateMultiple']);
});

// Product Routes
Route::get('/products/sort', [apiProductController::class, 'sortByPrice']);
Route::get('/products', [apiProductController::class, 'index']);
Route::get('/products/latest', [apiProductController::class, 'getLatestProducts']);
Route::get('/products/hot', [apiProductController::class, 'getHotProducts']);
Route::get('/products/best-selling', [apiProductController::class, 'getBestSellingProducts']);
Route::get('/products/products_paginate', [apiProductController::class, 'products_paginate']);
Route::get('/products/{id}', [apiProductController::class, 'show']);
Route::get('/products/search/{query}', [apiProductController::class, 'search']);
Route::get('/products/category/{categoryId}', [apiProductController::class, 'getProductsByCategory']);
Route::get('/products/category/url/{categoryUrl}', [apiProductController::class, 'getProductsByCategoryUrl']);
Route::get('/products/brand/{brandName}', [apiProductController::class, 'getProductsByBrand']);
Route::get('/products/{id}/related', [apiProductController::class, 'getRelatedProducts']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('/products', [apiProductController::class, 'store']);
    Route::put('/products/products/{id}', [apiProductController::class, 'update']);
    Route::delete('/products/{id}', [apiProductController::class, 'delete']);
});

// Variant Routes
Route::prefix('variants')->group(function () {
    Route::get('/', [apiProductVariantController::class, 'index']);
    Route::get('/product_id={product_id}', [apiProductVariantController::class, 'getProductsByProductId']);
    Route::get('/product_id={product_id}/variant_id={id}', [apiProductVariantController::class, 'getVariantByProductIdAndVariantId']);
    Route::get('/{id}', [apiProductVariantController::class, 'show']);
});
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::get('/admin/variants/product_id={product_id}', [apiProductVariantController::class, 'getProductsByProductIdWithoutHidden']);
    Route::get('/admin/variants/product_id={product_id}/variant_id={id}', [apiProductVariantController::class, 'getVariantByProductIdAndVariantIdWithoutHidden']);
    Route::post('/variants', [apiProductVariantController::class, 'store']);
    Route::put('/variants/{id}', [apiProductVariantController::class, 'update']);
    Route::delete('/variants/{id}', [apiProductVariantController::class, 'delete']);
});

// Category Routes
Route::get('/categories', [apiCategoryController::class, 'index']);
Route::get('/categories/{id}', [apiCategoryController::class, 'show']);
Route::get('/categorie/subcategories', [apiCategoryController::class, 'getSubcategories']);
Route::get('/categorie/parent-categories', [apiCategoryController::class, 'getParentCategories']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('/categories', [apiCategoryController::class, 'store']);
    Route::put('/categories/{id}', [apiCategoryController::class, 'update']);
    Route::delete('/categories/{id}', [apiCategoryController::class, 'destroy']);
});

// Product Review
Route::get('/products/{productId}/reviews', [apiProductReviewController::class, 'index']);
Route::get('/product-reviews', [apiProductReviewController::class, 'showProduct_reviewforUsser']);
Route::get('/admin/product-reviews', [apiProductReviewController::class, 'showProduct_reviewforAdmin']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('/product-reviews', [apiProductReviewController::class, 'store']);
    Route::post('/product-reviews/{id}/hide', [apiProductReviewController::class, 'hide']);
    Route::delete('/product-reviews/{id}', [apiProductReviewController::class, 'destroy']);
});

// Notifications
Route::get('/notifications', [apiNotificationController::class, 'getUserNotifications']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('/notifications', [apiNotificationController::class, 'createNotification']);
    Route::get('/admin/notifications', [apiNotificationController::class, 'showAllNotifications']);
    Route::put('/notifications/{id}/read', [apiNotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [apiNotificationController::class, 'destroy']);
});

Route::get('/promotions/active', [PromotionController::class, 'getActivePromotions']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('/promotions/create', [PromotionController::class, 'create']);
    Route::get('/promotions', [PromotionController::class, 'index']);
    Route::get('/promotions/{id}', [PromotionController::class, 'show']);
    Route::put('/promotions/{id}', [PromotionController::class, 'update']);
    Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);
    Route::get('/promotions/code/{code}', [PromotionController::class, 'getPromotionByCode']);
});



// Brand Routes
Route::get('/brands', [apiBrandController::class, 'index']);
Route::get('/brands/{id}', [apiBrandController::class, 'show']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('/brands', [apiBrandController::class, 'store']);
    Route::put('/brands/{id}', [apiBrandController::class, 'update']);
    Route::delete('/brands/{id}', [apiBrandController::class, 'destroy']);
});

// Order Routes
Route::get('orders', [OrderController::class, 'index']);
Route::get('orders/{order_id}', [OrderController::class, 'showOrder']);
Route::get('order-items/{orderId}', [OrderItemController::class, 'showOrderItems']);
Route::get('pending-orders', [OrderController::class, 'showPendingOrder']);
Route::get('orders/{order_id}/check-shipping', [OrderController::class, 'checkShippingInfo']);
Route::get('orders/shipping/list-orders-without-shipping', [OrderController::class, 'listOrdersWithoutShipping']);
Route::get('orders/shipping/list-orders-with-shipping', [OrderController::class, 'listOrdersWithShipping']);
Route::post('orders', [OrderController::class, 'store']);
Route::put('orders/{order_id}', [OrderController::class, 'update']);
Route::delete('orders/{order_id}', [OrderController::class, 'destroy']);


// Flash Sale Routes
Route::get('flash-sales/show-by-date', [FlashSaleController::class, 'showFlashSaleByDate']);
Route::get('flash-sales/{id}/products-and-variants', [FlashSaleProductController::class, 'showFlashSaleWithProductsAndVariants']);
Route::get('flash-sales', [FlashSaleController::class, 'index']);
Route::get('flash-sales/{id}/products', [FlashSaleController::class, 'showFlashSaleWithProducts']);
Route::get('flash-sales/{id}', [FlashSaleController::class, 'show']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('flash-sales/create', [FlashSaleController::class, 'store']);
    Route::put('flash-sales/update/{id}', [FlashSaleController::class, 'update']);
    Route::delete('flash-sales/delete/{id}', [FlashSaleController::class, 'destroy']);
    Route::post('flash-sales/add-product', [FlashSaleProductController::class, 'addProductToFlashSale']);
    Route::put('flash-sales/update-product/{id}', [FlashSaleProductController::class, 'updateProductFlashSale']);
    Route::delete('flash-sales/delete-product/{id}', [FlashSaleProductController::class, 'deleteProductFlashSale']);
});

// Flash Sale Product Routes
Route::get('check/flash-sales/products', [FlashSaleController::class, 'checkAndRemoveExpiredSales']);

// User Routes
Route::get('/users', [AdminUserController::class, 'index']);
Route::get('/users/search', [AdminUserController::class, 'searchUserByName']);
Route::get('/users/{id}', [AdminUserController::class, 'show']);
Route::get('/users/{userId}/orders', [AdminUserController::class, 'getOrdersByUser']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::put('/users/status/{id}', [AdminUserController::class, 'updateStatus']);
});

// Shipping Routes
Route::get('/shipping/{order_id}', [ShippingController::class, 'show']);
Route::post('orders/{order_id}/shipping', [ShippingController::class, 'store']);


// Price Route
Route::get('product/{id}/price', [apiProductController::class, 'getProductPrice']);

// Payment Routes
Route::get('payment/{order_id}', [PaymentController::class, 'getPaymentInfo']);
Route::get('payment/transaction/{transaction_code}', [PaymentController::class, 'getLatestTransaction']);
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::post('/create-payment', [VNPayController::class, 'createPayment']);
    Route::post('/update-payment-status', [VNPayController::class, 'updatePaymentStatus']);
});

// AI Routes
Route::post('/ai/search-product', [BoxChatAIController::class, 'searchProduct']);

// Thống kê
Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::get('/statistics/monthly/{year}', [StatisticsController::class, 'getMonthlyStatistics']);
    Route::get('/statistics/daily/{monthYear}', [StatisticsController::class, 'getDailyStatistics']);
    Route::get('/statistics/total-users', [StatisticsController::class, 'getTotalUsers']);
    Route::get('/statistics/total-orders', [StatisticsController::class, 'getTotalOrders']);
    Route::get('/statistics/total-products', [StatisticsController::class, 'getTotalProducts']);
    Route::get('/statistics/orders-by-status', [StatisticsController::class, 'getOrdersByStatus']);
    Route::get('/statistics/voucher-usage', [StatisticsController::class, 'getVoucherUsage']);
});

// User Routes

Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::put('/users/{id}/lock', [UserController::class, 'lockUsers']);
    Route::put('/users/{id}/unlock', [UserController::class, 'unlockUsers']);

    // quản lý admin
    Route::get('/admins', [UserController::class, 'manageAdmins']);
    Route::put('/admins/{id}/lock', [UserController::class, 'lockAdmins']);
});




Route::get('/search-products-all', [BoxChatAIController::class, 'searchProductByAll']);




Route::middleware(['ensure_token_is_valid'])->group(function () {
    Route::get('blogs', [BlogController::class, 'index']);  // Lấy danh sách blog
    Route::get('blogs/{id}', [BlogController::class, 'show']);  // Lấy chi tiết blog
    Route::post('blogs', [BlogController::class, 'store']);  // Thêm blog mới
    Route::put('blogs/{id}', [BlogController::class, 'update']);  // Cập nhật blog
    Route::delete('blogs/{id}', [BlogController::class, 'destroy']);
});