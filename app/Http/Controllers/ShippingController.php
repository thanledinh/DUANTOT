<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
                'shipping_method' => 'required|string',
                'shipping_cost' => 'required|numeric|min:0',
            ]);
            $order = Order::find($order_id);
            if (!$order) {
                return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
            }
            $shippingExists = Shipping::where('order_id', $order_id)->exists();
            if ($shippingExists) {
                return response()->json(['message' => 'Thông tin vận chuyển đã tồn tại cho đơn hàng này.'], 400);
            }
            $shipping = new Shipping();
            $shipping->order_id = $order_id;
            $shipping->full_name = $request->full_name;
            $shipping->email = $request->email;
            $shipping->shipping_address = $request->shipping_address;
            $shipping->city = $request->city;
            $shipping->district = $request->district;
            $shipping->phone = $request->phone;
            $shipping->shipping_method = $request->shipping_method;
            $shipping->shipping_cost = $request->shipping_cost;
            $shipping->shipping_status = 'pending';
            $shipping->save();
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
