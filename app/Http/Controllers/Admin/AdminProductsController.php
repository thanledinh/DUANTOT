<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class AdminProductsController extends Controller
{
    public function getProductVariants($productId)
    {
        // Lấy sản phẩm từ cơ sở dữ liệu
        $product = Product::with('variants')->findOrFail($productId); // Sử dụng with để eager load variants
        
        // Lấy các biến thể của sản phẩm
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



//list ra danh sách sản phẩm hàng tồn kho thấp nhất lưu ý có nhiều hàng tồn kho trong sản phẩm nhưng cái nào thấp nhất thì cứ lấy cả sản phẩm đó ra
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

    // lấy số lượng tồn kho theo phạm vi, ví dụ list ra danh sách sản phẩm có tồn kho từ 10 đến 20
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

    // cập nhật số lượng tồn kho của sản phẩm
    public function updateStockQuantity($productId, $variantId, $newStockQuantity)
    {
        $product = Product::findOrFail($productId);
        $variant = $product->variants()->findOrFail($variantId);

        $variant->stock_quantity = $newStockQuantity;
        $variant->save();

        return response()->json(['message' => 'Stock quantity updated successfully']);
    }
}
