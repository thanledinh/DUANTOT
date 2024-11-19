<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserPromotion;

class PromotionController extends Controller
{
    // Phương thức tạo mã khuyến mãi
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
            'applicable_products' => 'nullable|string',
            'min_quantity' => 'nullable|integer|min:1',
            'free_shipping' => 'nullable|boolean',
            'is_member_only' => 'nullable|boolean',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $data['quantity'] = $data['quantity'] ?? 0;

        $promotion = Promotion::create($data);
        return response()->json(['promotion' => $promotion], 201);
    }

    // Phương thức kiểm tra mã khuyến mãi và xử lý số lượng
    public function check(Request $request)
    {
        $promotion = Promotion::where('code', $request->input('code'))
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->where('quantity', '>', 0)
            ->first();

        if (!$promotion) {
            return response()->json(['valid' => false, 'message' => 'Mã khuyến mãi không khả dụng hoặc đã hết hạn'], 404);
        }

        $userId = Auth::id();

        $hasUsed = UserPromotion::where('promotion_id', $promotion->id)
            ->where('user_id', $userId)
            ->exists();

        if ($hasUsed) {
            return response()->json(['valid' => false, 'message' => 'Bạn đã sử dụng mã khuyến mãi này rồi.'], 403);
        }

        UserPromotion::create([
            'promotion_id' => $promotion->id,
            'user_id' => $userId,
        ]);

        $promotion->decrement('quantity');

        return response()->json(['valid' => true, 'promotion' => $promotion]);
    }

    // Phương thức cập nhật mã khuyến mãi, bao gồm số lượng
    public function update(Request $request, $id)
    {
        $promotion = Promotion::find($id);
        if ($promotion) {
            $data = $request->validate([
                'code' => 'required|string|max:255|unique:promotions,code,' . $id,
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
                'quantity' => 'nullable|integer|min:0',
            ]);
            $promotion->update($data);
            return response()->json(['promotion' => $promotion, 'message' => 'Cập nhật mã khuyến mãi thành công']);
        }
        return response()->json(['message' => 'Không tìm thấy mã khuyến mãi'], 404);
    }

    // Lấy danh sách tất cả mã khuyến mãi
    public function index()
    {
        $promotions = Promotion::all();
        return response()->json(['promotions' => $promotions]);
    }

    //lấy khuyến mãi còn thời hạn 
    public function getActivePromotions()
    {
        $promotions = Promotion::whereDate('end_date', '>=', now())->get();
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
