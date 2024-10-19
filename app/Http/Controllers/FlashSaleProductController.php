<?php

namespace App\Http\Controllers;

use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Models\Product;
use App\Models\FlashSale;

class FlashSaleProductController extends Controller
{
 
    public function addProductToFlashSale(Request $request)
    {
        $validatedData = $request->validate([
            'flash_sale_id' => 'required|exists:flash_sales,id',
            'product_id' => 'required|exists:products,id',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'quantity_limit_per_customer' => 'nullable|integer|min:1',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        try {
            // Lấy giá gốc của sản phẩm từ bảng products
            $product = Product::find($request->product_id);

            // Lấy tất cả các biến thể của sản phẩm
            $productVariants = $product->variants; // Giả sử bạn có mối quan hệ 'variants'

            // Tính tổng số lượng của tất cả các biến thể
            $totalStockQuantity = $productVariants->sum('stock_quantity');

            // Kiểm tra tổng số lượng sản phẩm còn đủ không
            if ($totalStockQuantity < $request->stock_quantity) {
                return response()->json([
                    'message' => 'Tổng số lượng sản phẩm không đủ để thêm vào Flash Sale.'
                ], 400);
            }

            // Tính toán giá sau khi giảm

            // Cập nhật trường sale (phần trăm giảm giá) trong bảng sản phẩm
            $product->update([
                'sale' => $request->discount_percentage, // Cập nhật phần trăm giảm giá
            ]);

            // Lưu thông tin Flash Sale
            FlashSaleProduct::create([
                'flash_sale_id' => $request->flash_sale_id,
                'product_id' => $request->product_id,
                'discount_percentage' => $request->discount_percentage,
                'quantity_limit_per_customer' => $request->quantity_limit_per_customer,
                'stock_quantity' => $request->stock_quantity,
            ]);

            return response()->json([
                'message' => 'Sản phẩm đã được thêm vào Flash Sale thành công.',
                'data' => [
                    'product_id' => $product->id,
                    'sale' => $request->discount_percentage,
                    'discount_percentage' => $request->discount_percentage,
                    'quantity_limit_per_customer' => $request->quantity_limit_per_customer,
                    'stock_quantity' => $request->stock_quantity,
                ]
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Lỗi khi thêm sản phẩm vào Flash Sale: ' . $e->getMessage());

            return response()->json([
                'message' => 'Có lỗi xảy ra khi thêm sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function updateProductFlashSale(Request $request, $id)
    {
        // Xác thực dữ liệu đầu vào
        $validatedData = $request->validate([
            'discount_percentage' => 'nullable|numeric|min:0',
            'quantity_limit_per_customer' => 'nullable|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        // Tìm sản phẩm trong Flash Sale
        $flashSaleProduct = FlashSaleProduct::find($id);
        if (!$flashSaleProduct) {
            return response()->json(['message' => 'Sản phẩm không tồn tại trong Flash Sale.'], 404);
        }

        try {
            // Cập nhật sản phẩm trong Flash Sale
            $flashSaleProduct->update($validatedData);

            return response()->json([
                'message' => 'Thông tin sản phẩm đã được cập nhật thành công.',
                'data' => $flashSaleProduct
            ], 200);
        } catch (QueryException $e) {
            \Log::error('Lỗi khi cập nhật sản phẩm trong Flash Sale: ' . $e->getMessage());

            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteProductFlashSale($id)
    {
        // Tìm sản phẩm trong Flash Sale
        $flashSaleProduct = FlashSaleProduct::find($id);
        if (!$flashSaleProduct) {
            return response()->json(['message' => 'Sản phẩm không tồn tại trong Flash Sale.'], 404);
        }

        try {
            // Cập nhật trường sale sau khi xóa
            $product = Product::find($flashSaleProduct->product_id);
            if ($product) {
                $product->update([
                    'sale' => 0, // Cập nhật phần trăm giảm giá
                ]);
            }

            // Xóa sản phẩm khỏi Flash Sale
            $flashSaleProduct->delete();

            return response()->json(['message' => 'Sản phẩm đã được xóa khỏi Flash Sale.'], 200);

        } catch (QueryException $e) {
            \Log::error('Lỗi khi xóa sản phẩm khỏi Flash Sale: ' . $e->getMessage());

            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showFlashSaleWithProductsAndVariants($id)
    {
        // Tìm Flash Sale theo ID
        $flashSale = FlashSale::with(['products.product.variants']) // Đảm bảo 'products' có quan hệ với 'FlashSaleProduct'
            ->find($id);

        if (!$flashSale) {
            return response()->json(['message' => 'Flash Sale không tồn tại.'], 404);
        }

        return response()->json([
            'data' => $flashSale
        ], 200);
    }
}
