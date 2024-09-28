<?php

namespace App\Http\Controllers;

use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;

class FlashSaleProductController extends Controller
{
    public function store(Request $request)
    {
        $flashSaleProduct = FlashSaleProduct::create($request->all());
        return response()->json($flashSaleProduct, 201);
    }

    public function destroy($id)
    {
        $flashSaleProduct = FlashSaleProduct::find($id);
        if (!$flashSaleProduct) {
            return response()->json(['error' => 'Sản phẩm không tồn tại trong flash sale'], 404);
        }
        $flashSaleProduct->delete();
        return response()->json(['message' => 'Xóa sản phẩm thành công khỏi flash sale']);
    }
}
