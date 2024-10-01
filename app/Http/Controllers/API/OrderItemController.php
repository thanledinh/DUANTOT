<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    public function showOrderItems($orderId, Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần phải đăng nhập để xem mục đơn hàng.'], 401);
        }
        $order = Order::where('id', $orderId)->where('user_id', $user->id)->first();
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng này hoặc đơn hàng không thuộc về bạn.'], 404);
        }
        $orderItems = OrderItem::where('order_id', $orderId) 
            ->with(['product', 'variant']) 
            ->get();
        if ($orderItems->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy mục đơn hàng nào cho đơn hàng này.'], 404);
        }
        return response()->json([
            'message' => 'Danh sách mục đơn hàng của đơn hàng này.',
            'order_items' => $orderItems
        ], 200);
    }
}
