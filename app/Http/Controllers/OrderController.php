<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\OrderItem;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần phải đăng nhập để xem đơn hàng.'], 401);
        }
        $orders = Order::where('user_id', $user->id)
            ->with('items.product')
            ->get();
        return response()->json([
            'message' => 'Danh sách đơn hàng của bạn',
            'orders' => $orders
        ], 200);
    }
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Lỗi: Người dùng không tồn tại.'], 401);
        }

        $request->validate([
            'id_promotion' => 'nullable|integer',
            'order_date' => 'required|date',
            'total_price' => 'required|numeric',
            'status' => 'required|string',
            'payment_method' => 'required|string',
            'sale' => 'nullable|numeric',
            'items' => 'required|array',

            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.variant_id' => 'required|integer|min:1',
        ]);

        try {
            $order = new Order();
            $order->user_id = $user->id;
            $order->id_promotion = $request->id_promotion ?? null;
            $order->order_date = $request->order_date;
            $order->total_price = $request->total_price;
            $order->status = $request->status;
            $order->payment_method = $request->payment_method;
            $order->sale = $request->sale ?? 0;
            $order->save();

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'variant_id' => $item['variant_id'],
                    'price' => $item['price'],
                ]);
            }

            return response()->json([
                'message' => 'Đơn hàng đã được tạo thành công.',
                'order' => $order
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi tạo đơn hàng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Lỗi: Người dùng không tồn tại.'], 401);
        }
        $order = Order::where('user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại hoặc không thuộc về người dùng.'], 404);
        }
        if ($order->status == 'processed' || $order->status == 'completed') {
            return response()->json(['message' => 'Không thể thay đổi trạng thái của đơn hàng đã được xử lý hoặc hoàn thành.'], 403);
        }
        $request->validate([
            'status' => 'required|string|in:pending,processed,completed,canceled',
        ]);
        try {
            $order->update([
                'status' => $request->status,
            ]);
            return response()->json([
                'message' => 'Trạng thái đơn hàng đã được cập nhật thành công.',
                'order' => $order
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi cập nhật đơn hàng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Lỗi: Người dùng không tồn tại.'], 401);
        }
        $order = Order::where('user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại hoặc không thuộc về người dùng.'], 404);
        }
        if ($order->status == 'processed' || $order->status == 'completed') {
            return response()->json(['message' => 'Không thể xóa đơn hàng đã được xử lý hoặc hoàn thành.'], 403);
        }
        try {
            OrderItem::where('order_id', $order->id)->delete();
            $order->delete();
            return response()->json(['message' => 'Đơn hàng đã được xóa thành công.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi xóa đơn hàng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}