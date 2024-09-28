<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function index()
    {
        $flashSales = FlashSale::with('products')->get();
        return response()->json($flashSales);
    }
    public function show($id)
    {
        $flashSale = FlashSale::with('products')->find($id);
        if (!$flashSale) {
            return response()->json(['error' => 'Flash Sale không tồn tại'], 404);
        }
        return response()->json($flashSale);
    }
    public function store(Request $request)
    {
        $flashSale = FlashSale::create($request->all());
        return response()->json($flashSale, 201);
    }
    public function update(Request $request, $id)
    {
        $flashSale = FlashSale::find($id);
        if (!$flashSale) {
            return response()->json(['error' => 'Flash Sale không tồn tại'], 404);
        }
        $flashSale->update($request->all());
        return response()->json($flashSale);
    }
    public function destroy($id)
    {
        $flashSale = FlashSale::find($id);
        if (!$flashSale) {
            return response()->json(['error' => 'Flash Sale không tồn tại'], 404);
        }
        $flashSale->delete();
        return response()->json(['message' => 'Xóa Flash Sale thành công']);
    }
}
