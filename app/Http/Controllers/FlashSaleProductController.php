<?php

namespace App\Http\Controllers;

use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class FlashSaleProductController extends Controller
{
    public function addProductToFlashSale(Request $request)
    {
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
                $request->only([
                    'flash_sale_id',
                    'product_id',
                    'discount_price',
                    'original_price',
                    'quantity_limit_per_customer',
                    'stock_quantity'
                ])
            );
            return response()->json([
                'message' => '.',
                'data' => $flashSaleProduct
            ], 201);
        } catch (QueryException $e) {
            return response()->json([
                'message' => '.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
