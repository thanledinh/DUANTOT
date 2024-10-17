<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    // Tạo mới mã khuyến mãi
    public function create(Request $request)
    {
        $promotion = Promotion::create($request->all());
        return response()->json(['promotion' => $promotion], 201);
    }

    // Kiểm tra mã khuyến mãi có hợp lệ không
    public function check(Request $request)
    {
        $promotion = Promotion::where('code', $request->input('code'))
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        if ($promotion && $promotion->isValidForOrder($request->order)) {
            return response()->json(['valid' => true, 'promotion' => $promotion]);
        }

        return response()->json(['valid' => false], 404);
    }

    // Lấy danh sách tất cả mã khuyến mãi
    public function index()
    {
        $promotions = Promotion::all();
        return response()->json(['promotions' => $promotions]);
    }

    // Lấy chi tiết một mã khuyến mãi
    public function show($id)
    {
        $promotion = Promotion::find($id);

        if ($promotion) {
            return response()->json(['promotion' => $promotion]);
        }

        return response()->json(['message' => 'Promotion not found'], 404);
    }

    // Cập nhật mã khuyến mãi
    public function update(Request $request, $id)
    {
        $promotion = Promotion::find($id);

        if ($promotion) {
            $promotion->update($request->all());
            return response()->json(['promotion' => $promotion, 'message' => 'Promotion updated successfully']);
        }

        return response()->json(['message' => 'Promotion not found'], 404);
    }

    // Xóa mã khuyến mãi
    public function destroy($id)
    {
        $promotion = Promotion::find($id);

        if ($promotion) {
            $promotion->delete();
            return response()->json(['message' => 'Promotion deleted successfully']);
        }

        return response()->json(['message' => 'Promotion not found'], 404);
    }
}

