<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\Shipping;

use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function store(Request $request, $order_id)
    {
        try {
            $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'shipping_address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                'ward' => 'required|string|max:255',
                'shipping_method' => 'required|string',
            ]);
            $order = Order::find($order_id);
            if (!$order) {
                return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
            }
            $shippingExists = Shipping::where('order_id', $order_id)->exists();
            if ($shippingExists) {
                return response()->json(['message' => 'Thông tin vận chuyển đã tồn tại cho đơn hàng này.'], 400);
            }
            $shipping_cost = 0;
            if ($order->id_promotion) {
                $promotion = Promotion::find($order->id_promotion);
                if ($promotion && $promotion->free_shipping) {
                    $shipping_cost = 0;
                }
            }
            // {{ edit_1 }} - Kiểm tra nếu phí vận chuyển chưa được cộng
            if (!$order->shipping()->exists() && $shipping_cost > 0) {
                $order->total_price += $shipping_cost;
            }
            $order->save();
            $shipping = new Shipping();
            $shipping->order_id = $order->id;
            $shipping->full_name = $request->full_name;
            $shipping->email = $request->email;
            $shipping->shipping_address = $request->shipping_address;
            $shipping->city = $request->city;
            $shipping->district = $request->district;
            $shipping->phone = $request->phone;
            $shipping->shipping_method = $request->shipping_method;
            $shipping->ward = $request->ward;
            $shipping->shipping_cost = $shipping_cost;
            $shipping->shipping_status = 'pending';
            $shipping->save();

            // Cập nhật trạng thái đơn hàng
            $order->update([
                'status' => 'Tiếp nhận', // Set status to paid
            ]);



            return response()->json([
                'message' => 'Thông tin vận chuyển đã được thêm thành công.',
                'shipping' => $shipping
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
    public function show($order_id)
    {
        try {
            $order = Order::find($order_id);
            if (!$order) {
                return response()->json(['message' => 'không tìm thấy đơn hàng'], 404);
            }
            $shipping = Shipping::where('order_id', $order_id)->first();
            if (!$shipping) {
                return response()->json(['message' => 'không tin vận chuyển không tồn tại'], 404);
            }
            return response()->json([
                'message' => 'thông tin vận chuyển',
                'shipping' => $shipping
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
