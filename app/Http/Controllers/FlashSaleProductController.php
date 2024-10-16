<?php

namespace App\Http\Controllers;

use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class FlashSaleProductController extends Controller
{
    public function addProductFlashSale(Request $request)
    {
        // $this->authorize('isAdmin'); 
        $validatedData = $request->validate([
            'flash_sale_id' => 'required|exists:flash_sales,id',
            'product_id' => 'required|exists:products,id',
            'discount_price' => 'required|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'quantity_limit_per_customer' => 'nullable|integer|min:1',
            'stock_quantity' => 'required|integer|min:0',
        ]);
        try {
            $flashSaleProduct = FlashSaleProduct::create(
                $validatedData
            );
            return response()->json([
                'message' => 'Sản phẩm đã được thêm vào Flash Sale thành công.',
                'data' => $flashSaleProduct
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi thêm sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateProductFlashSale(Request $request, $id)
    {
        // $this->authorize('isAdmin'); 
        $validatedData = $request->validate([
            'discount_price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'quantity_limit_per_customer' => 'nullable|integer|min:1',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);
        $flashSaleProduct = FlashSaleProduct::find($id);
        if (!$flashSaleProduct) {
            return response()->json(['message' => 'Sản phẩm không tồn tại trong Flash Sale.'], 404);
        }
        try {
            $flashSaleProduct->update($validatedData);
            return response()->json([
                'message' => 'Thông tin sản phẩm đã được cập nhật thành công.',
                'data' => $flashSaleProduct
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function deleteProductFlashSale($id)
    {
        // $this->authorize('isAdmin'); 
        $flashSaleProduct = FlashSaleProduct::find($id);
        if (!$flashSaleProduct) {
            return response()->json(['message' => 'Sản phẩm không tồn tại trong Flash Sale.'], 404);
        }
        try {
            $flashSaleProduct->delete();
            return response()->json(['message' => 'Sản phẩm đã được xóa khỏi Flash Sale.'], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa sản phẩm.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
