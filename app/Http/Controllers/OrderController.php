<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\Product;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần phải đăng nhập để xem đơn hàng.'], 401);
        }
        $orders = Order::where('user_id', $user->id)
            ->with('items.product', 'items.variant')
            ->get();
        if ($request->has('order_id')) {
            $order = $orders->where('id', $request->order_id)->first();
            return response()->json([
                'message' => 'Thông tin đơn hàng của bạn',
                'order' => $order
            ], 200);
        }
        return response()->json([
            'message' => 'Danh sách đơn hàng của bạn',
            'orders' => $orders
        ], 200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'id_promotion' => 'nullable|integer|exists:promotions,id',
            'payment_method' => 'required|string|in:cash,vnpay',
            'note' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.variant_id' => 'required|integer|min:1',
            'items.*.sale' => 'nullable|numeric',
        ]);

        try {
            $total_price = 0;
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $variant = ProductVariant::find($item['variant_id']);

                // Kiểm tra số lượng tồn kho
                if ($variant->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'message' => 'Sản phẩm ' . $product->name . ' chỉ còn ' . $variant->stock_quantity . ' sản phẩm trong kho.',
                        'id_product' => $product->id,
                        'error_code' => 'INSUFFICIENT_STOCK',
                        'available_stock' => $variant->stock_quantity
                    ], 400);
                }

                if ($product && $this->isSaleValid($product, $item['sale'])) {
                    $discounted_price = $item['price'] - ($item['price'] * $item['sale'] / 100);
                    
                    // Tính tổng giá cho sản phẩm với logic mới
                    if ($item['quantity'] > 1) {
                        // Áp dụng giảm giá cho 1 sản phẩm, các sản phẩm còn lại tính giá gốc
                        $total_price += $discounted_price + ($item['price'] * ($item['quantity'] - 1));
                    } else {
                        // Áp dụng giảm giá cho sản phẩm
                        $total_price += $discounted_price * $item['quantity'];
                    }
                } else {
                    return response()->json([
                        'message' => 'Giá trị sale không hợp lệ cho sản phẩm ' . $product->name,
                        'id_product' => $product->id,
                        'error_code' => 'INVALID_SALE_VALUE',
                        'product_db_sale' => $product->sale,
                        'provided_sale' => $item['sale'],
                        'is_numeric_sale' => is_numeric($item['sale'])
                    ], 400);
                }
            }
            $order = new Order();
            if (auth()->guard('api')->check()) {
                $order->user_id = auth()->guard('api')->id();
            } else {
                $order->user_id = null;
            }
            $order->tracking_code = strtoupper(Str::random(10));
            $order->id_promotion = $request->id_promotion ?? null;
            
            $order->order_date = now();
            $order->total_price = $total_price;
            $order->status = 'pending';
            $order->payment_method = $request->payment_method;

            $order->note = $request->note ?? null;
            $shipping_cost = 40000;
            if ($request->id_promotion) {
                $promotion = Promotion::find($request->id_promotion);
                if ($promotion) {
                    if ($total_price >= $promotion->minimum_order_value) {
                        if ($promotion->discount_percentage) {
                            $discount = ($total_price * $promotion->discount_percentage) / 100;
                            $order->total_price -= $discount;
                        } elseif ($promotion->discount_amount) {
                            $order->total_price -= $promotion->discount_amount;
                        }
                        if ($promotion->free_shipping) {
                            $shipping_cost = 0;
                        }
                        $order->total_price = max(0, $order->total_price);
                    }
                }
            }
            $order->total_price += $shipping_cost;
            $order->save();
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product && $this->isSaleValid($product, $item['sale'])) {
                    $discounted_price = $item['price'] - ($item['price'] * $item['sale'] / 100);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'variant_id' => $item['variant_id'],
                        'price' => $discounted_price,
                        'sale' => $item['sale'] ?? 0,
                    ]); 
                } else {
                    return response()->json([
                        'message' => 'Giá trị sale không hợp lệ cho sản phẩm ' . $product->name,
                        'id_product' => $product->id,
                        'error_code' => 'INVALID_SALE_VALUE',
                        'product_db_sale' => $product->sale,
                        'provided_sale' => $item['sale'],
                        'is_numeric_sale' => is_numeric($item['sale'])
                    ], 400);
                }
            }
            return response()->json([
                'message' => 'Đơn hàng đã được tạo thành công.',
                'order' => $order,
                'shipping_cost' => $shipping_cost
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi tạo đơn hàng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function isSaleValid($product, $sale)
    {
        // Kiểm tra nếu sale gửi lên giống với sale trong DB
        return is_numeric($sale) && $sale == $product->sale;
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Lỗi: Người dùng không tồn tại.'], 401);
        }
        $order = Order::where('user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại hoặc không thuộc về người dng.'], 404);
        }
        if ($order->status == 'processed' || $order->status == 'completed') {
            return response()->json(['message' => 'Không thể thay đổi trạng thái của đơn hàng đã được xử lý hoặc hoàn thành.'], 403);
        }
        $request->validate([
            'status' => 'nullable|string|in:pending,processed,completed,canceled',
            'payment_method' => 'nullable|string|in:credit_card,cash_on_delivery,bank_transfer',
            'note' => 'nullable|string',
        ]);
        try {
            $order->update(array_filter([
                'status' => $request->status,
                'note' => $request->note,
                'payment_method' => $request->payment_method === 'cash_on_delivery' ? 'cash_on_delivery' : $order->payment_method,
            ]));
            if ($request->payment_method === 'cash_on_delivery') {
                $order->status = 'confirmed';
                $order->save();
            }
            return response()->json([
                'message' => 'Đơn hàng đã được cập nhật thành công.',
                'order' => $order
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi cập nhật đơn hàng.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($order_id, Request $request)
    {
        $order = Order::find($order_id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xóa đơn hàng này.'], 403);
        }

        // {{ edit_1 }} - Hoàn lại stock quantity cho từng item trong đơn hàng
        foreach ($order->items as $item) {
            $variant = ProductVariant::find($item->variant_id); // Get the product variant
            if ($variant) {
                $variant->stock_quantity += $item->quantity; // Restore stock quantity
                $variant->save(); // Save the updated variant
            }
        }
        $order->delete();
        return response()->json(['message' => 'Đơn hàng đã được xóa thành công.'], 200);
    }
    public function showOrder($id)
    {
        $order = Order::with('items.product', 'items.variant')->find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }
        return response()->json([
            'message' => 'Đơn hàng đã được lấy thành công.',
            'order' => $order
        ], 200);
    }
    public function showPendingOrder(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần phải đăng nhập để xem đơn hàng.'], 401);
        }

        $orders = Order::where('status', 'pending')
            ->where('user_id', $user->id) // Lọc theo user_id
            ->with(['items.product', 'items.variant'])
            ->get();

        return response()->json([
            'message' => 'Đơn hàng đã được lấy thành công.',
            'orders' => $orders
        ], 200);
    }

    // Xóa đơn hàng có trạng thái pending
    public function deletePendingOrder($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        // Kiểm tra trạng thái của đơn hàng
        if ($order->status !== 'pending') { // Thêm kiểm tra trạng thái
            return response()->json(['message' => 'Chỉ có thể xóa đơn hàng có trạng thái pending.'], 403);
        }

        $order->delete();
        return response()->json(['message' => 'Đơn hàng đã được xóa thành công.'], 200);
    }

    // Kiểm tra đơn hàng đã có thông tin shipping hay chưa
    public function checkShippingInfo($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        $hasShipping = $order->shipping()->exists();

        return response()->json([
            'order_id' => $orderId,
            'has_shipping_info' => $hasShipping,
            'message' => $hasShipping ? 'Đơn hàng đã có thông tin shipping.' : 'Đơn hàng chưa có thông tin shipping.'
        ], 200);
    }

    // Danh sách đơn hàng của user chưa có shipping
    public function listOrdersWithoutShipping(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần phải đăng nhập để xem đơn hàng.'], 401);
        }

        $orders = Order::where('user_id', $user->id)
            ->with('items.product', 'items.variant')
            ->get();

        $ordersWithoutShipping = [];
        foreach ($orders as $order) {
            if (!$order->shipping()->exists()) {
                $ordersWithoutShipping[] = $order; // Add the entire order
            }
        }

        return response()->json([
            'message' => 'Danh sách đơn hàng chưa có thông tin shipping.',
            'orders' => $ordersWithoutShipping
        ], 200);
    }

    // danh sách các đơn hàng có shipping
    public function listOrdersWithShipping(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Bạn cần phải đăng nhập để xem đơn hàng.'], 401);
        }

        $orders = Order::where('user_id', $user->id)
            ->with('items.product', 'items.variant')
            ->get();

        $ordersWithShipping = [];
        foreach ($orders as $order) {
            if ($order->shipping()->exists()) {
                $ordersWithShipping[] = $order; // Add the entire order
            }
        }

        return response()->json([
            'message' => 'Danh sách ơn hàng có thông tin shipping.',
            'orders' => $ordersWithShipping
        ], 200);
    }
}
