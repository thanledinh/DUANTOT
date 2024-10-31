<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Auth;

class AdminProductsController extends Controller
{

    // Lấy danh sách các biến thể của sản phẩm có stock quantity từ thấp tới cao
    public function index()
    {
        $products = Product::with('variants')
            ->get()
            ->map(function ($product) {
                // Tìm biến thể có số lượng tồn kho thấp nhất
                $lowestStockVariant = $product->variants->sortBy('stock_quantity')->first();

                // Tạo mảng chứa tất cả các biến thể
                $variants = $product->variants->map(function ($variant) {
                    return [
                        'variant_id' => $variant->id,
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'stock_quantity' => $variant->stock_quantity,
                    ];
                });

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'lowest_stock' => $lowestStockVariant ? $lowestStockVariant->stock_quantity : 0,
                    'variants' => $variants, // Bao gồm tất cả các biến thể
                ];
            })
            ->sortBy('lowest_stock');

        return response()->json($products->values());
    }

    public function getProductVariants($productId)
    {
        $product = Product::with('variants')->findOrFail($productId);
        
        $variants = $product->variants->map(function ($variant) {
            return [
                'variant_id' => $variant->id,
                'size' => $variant->size,
                'color' => $variant->color,
                'stock_quantity' => $variant->stock_quantity,
            ];
        });

        return response()->json([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variants' => $variants
        ]);
    }

    public function getProductsWithLowestStock()
    {
        $products = Product::with(['variants' => function ($query) {
            $query->orderBy('stock_quantity', 'asc');
        }])
        ->get()
        ->map(function ($product) {
            $lowestStockVariant = $product->variants->first();
            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'lowest_stock' => $lowestStockVariant ? $lowestStockVariant->stock_quantity : 0,
                'variant_id' => $lowestStockVariant ? $lowestStockVariant->id : null,
                'size' => $lowestStockVariant ? $lowestStockVariant->size : null,
                'color' => $lowestStockVariant ? $lowestStockVariant->color : null,
            ];
        })
        ->sortBy('lowest_stock');

        return response()->json($products);
    }

    public function getProductsByStockQuantity($minStock, $maxStock)
    {
        $products = Product::with(['variants' => function ($query) use ($minStock, $maxStock) {
            $query->whereBetween('stock_quantity', [$minStock, $maxStock])
                  ->orderBy('stock_quantity', 'asc');
        }])
        ->whereHas('variants', function ($query) use ($minStock, $maxStock) {
            $query->whereBetween('stock_quantity', [$minStock, $maxStock]);
        })
        ->get()
        ->map(function ($product) {
            $lowestStockVariant = $product->variants->first();
            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'lowest_stock' => $lowestStockVariant->stock_quantity,
                'variant_id' => $lowestStockVariant->id,
                'size' => $lowestStockVariant->size,
                'color' => $lowestStockVariant->color,
            ];
        })
        ->sortBy('lowest_stock');

        return response()->json($products);
    }

    public function updateStockQuantity($productId, $variantId, $newStockQuantity)
    {
        // Lấy ID người dùng
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $product = Product::findOrFail($productId);
        $variant = $product->variants()->findOrFail($variantId);

        $oldQuantity = $variant->stock_quantity;
        $variant->stock_quantity = $newStockQuantity;
        $variant->save();

        StockHistory::create([
            'variant_id' => $variantId,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newStockQuantity,
            'changed_by' => $userId, // Lưu ID người dùng thay vì tên
        ]);

        return response()->json(['message' => 'Stock quantity updated successfully', 'changed_by' => $userId]);
    }

    public function updateProductStatus($productId, $isActive)
    {
        $product = Product::findOrFail($productId);
        $product->is_active = $isActive;
        $product->save();

        return response()->json(['message' => 'Product status updated successfully']);
    }

    public function searchProducts(Request $request)
    {
        $query = Product::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        $products = $query->get();

        return response()->json($products);
    }

    // Lấy lịch sử thay đổi tồn kho
    public function getStockHistory($variantId)
    {
        $history = StockHistory::where('variant_id', $variantId)->orderBy('changed_at', 'desc')->get();

        return response()->json($history);
    }

    // API thông báo khi tồn kho dưới 10
    public function getLowStockAlerts()
    {
        $products = Product::with(['variants' => function ($query) {
            $query->where('stock_quantity', '<', 10);
        }])
        ->whereHas('variants', function ($query) {
            $query->where('stock_quantity', '<', 10);
        })
        ->get()
        ->map(function ($product) {
            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'variant_id' => $variant->id,
                        'size' => $variant->size,
                        'color' => $variant->color,
                        'stock_quantity' => $variant->stock_quantity,
                    ];
                }),
            ];
        });

        return response()->json($products);
    }
}
