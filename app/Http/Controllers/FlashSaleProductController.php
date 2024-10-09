<?php

namespace App\Http\Controllers;

use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;

class FlashSaleProductController extends Controller
{
    // API để thêm sản phẩm vào Flash Sale
    public function addProductToFlashSale(Request $request)
    {
        // Validate request để đảm bảo flash_sale_id và product_id hợp lệ
        $request->validate([
            'flash_sale_id' => 'required|exists:flash_sales,id',
            'product_id' => 'required|exists:products,id',
            'discount_price' => 'required|numeric',
            'original_price' => 'required|numeric',
            'quantity_limit_per_customer' => 'nullable|integer',
            'stock_quantity' => 'required|integer',
        ]);

        // Tạo mới bản ghi trong bảng flash_sales_products
        $flashSaleProduct = FlashSaleProduct::create([
            'flash_sale_id' => $request->flash_sale_id,
            'product_id' => $request->product_id,
            'discount_price' => $request->discount_price,
            'original_price' => $request->original_price,
            'quantity_limit_per_customer' => $request->quantity_limit_per_customer,
            'stock_quantity' => $request->stock_quantity,
        ]);

        // Trả về response với dữ liệu đã lưu
        return response()->json([
            'message' => 'Product added to Flash Sale successfully.',
            'data' => $flashSaleProduct
        ], 201);
    }
}
