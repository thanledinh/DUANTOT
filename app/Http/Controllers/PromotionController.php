<?php
namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function create(Request $request)
    {
        $promotion = Promotion::create($request->all());
        return response()->json(['promotion' => $promotion], 201);
    }
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
    public function apply(Request $request)
    {
        $promotion = Promotion::where('code', $request->input('code'))->first();
        if (!$promotion || !$promotion->isValidForOrder($request->order)) {
            return response()->json(['error' => 'Mã khuyến mãi không hợp lệ'], 400);
        }
        //  giảm giá hoặc khuyến mãi khác cho đơn hàng
        $order = $request->order;
        if ($promotion->discount_percentage) {
            $order->total -= ($order->total * $promotion->discount_percentage / 100);
        } elseif ($promotion->discount_amount) {
            $order->total -= $promotion->discount_amount;
        }
        if ($promotion->free_shipping) {
            $order->shipping_fee = 0;
        }
        return response()->json(['order' => $order]);
    }
}
