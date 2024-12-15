<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\Shipping;
use App\Models\ProductVariant;
use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ShippingConfirmation;

class ShippingController extends Controller
{
    /**
     * Create shipping information for an order.
     */
    public function store(Request $request, $order_id)
    {
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

        DB::beginTransaction();
        try {
            // Find the order
            $order = Order::find($order_id);
            if (!$order) {
                return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
            }

            // Check if shipping already exists
            if ($order->shipping) {
                return response()->json(['message' => 'Thông tin vận chuyển đã tồn tại cho đơn hàng này.'], 400);
            }

            // Calculate shipping cost
            $shipping_cost = $this->calculateShippingCost($order);

            // Save shipping information
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

            // Deduct stock quantities
            $this->deductStock($order);

            // Update order status
            $order->update(['status' => 'processing']);

            DB::commit();

            // Gửi email xác nhận vận chuyển
            Mail::to($shipping->email)->send(new ShippingConfirmation($shipping, $order));

            return response()->json([
                'message' => 'Thông tin vận chuyển đã được thêm thành công.',
                'shipping' => $shipping
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show shipping information for an order.
     */
    public function show($order_id)
    {
        try {
            $order = Order::with('shipping')->find($order_id);
            if (!$order) {
                return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
            }

            if (!$order->shipping) {
                return response()->json(['message' => 'Thông tin vận chuyển không tồn tại.'], 404);
            }

            return response()->json([
                'message' => 'Thông tin vận chuyển',
                'shipping' => $order->shipping
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Calculate shipping cost for an order.
     */
    private function calculateShippingCost(Order $order)
    {
        $shipping_cost = 40000; // Default shipping cost

        // Check promotion for free shipping
        if ($order->id_promotion) {
            $promotion = Promotion::find($order->id_promotion);
            if ($promotion && $promotion->free_shipping) {
                $shipping_cost = 0;
            }
        }

        return $shipping_cost;
    }

    /**
     * Deduct stock quantities for an order.
     */
    private function deductStock(Order $order)
    {
        foreach ($order->items as $item) {
            $variant = ProductVariant::find($item->variant_id);
            if ($variant) {
                if ($variant->stock_quantity < $item->quantity) {
                    throw new \Exception('Sản phẩm không đủ tồn kho: ' . $variant->product->name);
                }
                $variant->stock_quantity -= $item->quantity;
                $variant->save();
            }

            $flashSaleProduct = FlashSaleProduct::where('product_id', $item->product_id)->first();
            if ($flashSaleProduct) {
                if ($flashSaleProduct->stock_quantity < 1) {
                    throw new \Exception('Sản phẩm trong chương trình Flash Sale đã hết hàng.');
                }
                $flashSaleProduct->stock_quantity -= 1;
                $flashSaleProduct->save();
            }
        }
    }
}
