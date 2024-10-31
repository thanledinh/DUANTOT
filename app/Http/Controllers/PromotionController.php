<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    // Tạo mới mã khuyến mãi
    public function create(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:255|unique:promotions,code',
            'description' => 'required|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'promotion_type' => 'required|string',
            'minimum_order_value' => 'nullable|numeric|min:0',
            'applicable_products' => 'nullable|string', // Assuming you store product/category IDs as a string
            'min_quantity' => 'nullable|integer|min:1',
            'free_shipping' => 'nullable|boolean',
            'is_member_only' => 'nullable|boolean',
        ]);

        $promotion = Promotion::create($data);

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
            $data = $request->validate([
                'code' => 'required|string|max:255|unique:promotions,code,' . $promotion->id,
                'description' => 'required|string',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'discount_amount' => 'nullable|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'promotion_type' => 'required|string',
                'minimum_order_value' => 'nullable|numeric|min:0',
                'applicable_products' => 'nullable|string',
                'min_quantity' => 'nullable|integer|min:1',
                'free_shipping' => 'nullable|boolean',
                'is_member_only' => 'nullable|boolean',
            ]);

            $promotion->update($data);

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

    // lấy thông tin mã khuyến mãi theo tên mã khuyến mãi
    public function getPromotionByCode($code)
    {
        $promotion = Promotion::where('code', $code)->first();
        return response()->json(['promotion' => $promotion]);
    }

    // lấy thông tin mã khuyến mãi theo id
    public function getPromotionById($id)
    {
        $promotion = Promotion::find($id);
        return response()->json(['promotion' => $promotion]);
    }
}
