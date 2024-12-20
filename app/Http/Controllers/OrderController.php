<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
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
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.sale' => 'nullable|numeric',
        ]);
    
        try {
            // 1. Kiểm tra lịch sử bom hàng của khách hàng
            $bombOrderCount = Order::where('status', 'canceled')
            ->whereHas('shipping', function ($query) use ($request) {
            $query->where('phone', $request->phone)
            ->orWhere('email', $request->email);
            })
            ->count();

            if ($bombOrderCount >= 3 && $request->payment_method == 'cash') {
            return response()->json([
            'message' => 'Bạn đã có hơn 3 đơn hàng bị hủy. Vui lòng thanh toán trước để tiếp tục đặt hàng.'
            ], 400);
            }
            
                $items = $request->items;
                $productIds = array_column($items, 'product_id');
                $variantIds = array_column($items, 'variant_id');
        
            // Lấy thông tin sản phẩm và biến thể
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');
    
            $total_price = 0;
    
            foreach ($items as $item) {
                $product = $products[$item['product_id']] ?? null;
                $variant = $variants[$item['variant_id']] ?? null;
                
                if (!$product || !$variant || $variant->stock_quantity < $item['quantity']) {
                return response()->json([
                    'message' => 'Số lượng tồn kho không đủ cho sản phẩm ' . ($product->name ?? '')
                ], 400);
                }
                if (!$product || !$variant) {
                    return response()->json(['message' => 'Sản phẩm hoặc biến thể không hợp lệ.'], 400);
                }
                // kiễm tra sản phẩm có tham gia sale không
                $flashSaleProduct = FlashSaleProduct::where('product_id', $item['product_id'])->first();
                
                if ($flashSaleProduct) {
                $flashSale = FlashSale::find($flashSaleProduct->flash_sale_id);

                if ($flashSale && now()->greaterThan($flashSale->end_time)) {
                    return response()->json(['message' => 'Flash sale đã hết hạn cho sản phẩm ' . $product->name], 400);
                }
                }

                // Nếu giá được người dùng gửi không đúng, thay bằng giá của variant
                if ($item['price'] !== null && $item['price'] != $variant->price) {
                    $item['price'] = $variant->price; // Thay giá bằng giá của variant
                }
            
                // Tính toán giá sau khi áp dụng giảm giá
                $final_price = $this->calculateDiscountedPrice($item['price'], $product->sale);
            
                // Kiểm tra giá trị sale hợp lệ cho sản phẩm
                if ($item['sale'] !== null && $item['sale'] > $product->sale) {
                    return response()->json([
                        'message' => 'Giá trị sale không hợp lệ cho sản phẩm ' . $product->name,
                        'id_product' => $product->id,
                        'error_code' => 'INVALID_SALE_VALUE',
                        'product_db_sale' => $product->sale,
                        'provided_sale' => $item['sale'],
                    ], 400);
                }
            
                // Cập nhật lại giá trị cuối cùng cho `price` và `sale` của mỗi `OrderItem`
                $total_price += $final_price * $item['quantity'];

                  // Trừ tồn kho
                 $variant->stock_quantity -= $item['quantity'];
                 $variant->save();
                 $deductedStock[] = ['variant' => $variant, 'quantity' => $item['quantity']]; // Lưu lại tồn kho đã trừ
            }
            
    
            // Xử lý mã khuyến mãi
            $promotion = null;
            $shipping_cost = 40000;
    
            if ($request->id_promotion) {
                $promotion = Promotion::find($request->id_promotion);
    
                if ($promotion && $promotion->quantity > 0) {
                    if ($total_price < $promotion->minimum_order_value) {
                        return response()->json([
                            'message' => 'Giá trị đơn hàng chưa đủ điều kiện.',
                        ], 400);
                    }
    
                    if ($promotion->discount_percentage) {
                        $discount = ($total_price * $promotion->discount_percentage) / 100;
                        $total_price -= $discount;
                    } elseif ($promotion->discount_amount) {
                        $total_price -= $promotion->discount_amount;
                    }
    
                    if ($promotion->free_shipping) {
                        $shipping_cost = 0;
                    }
    
                    $promotion->decrement('quantity');
                } else {
                    return response()->json(['message' => 'Mã khuyến mãi không hợp lệ hoặc đã hết.'], 400);
                }
            }
    
            $order = Order::create([
                'user_id' => auth()->guard('api')->id() ?? null,
                'tracking_code' => strtoupper(Str::random(10)),
                'id_promotion' => $request->id_promotion,
                'order_date' => now(),
                'total_price' => $total_price + $shipping_cost,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                
            ]);
    
            foreach ($items as $item) {
                // Sử dụng giá đã được điều chỉnh (nếu có)
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'sale' => $products[$item['product_id']]->sale,
                ]);
            }
            if ($request->has('cancel_order') && $request->cancel_order) {
                $this->restoreStock($order); // Khôi phục tồn kho
                $order->update(['status' => 'canceled']);
                return response()->json(['message' => 'Đơn hàng đã bị hủy.', 'order' => $order], 200);
            }
            return response()->json(['message' => 'Đơn hàng đã được tạo thành công.', 'order' => $order], 201);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Đã xảy ra lỗi.', 'error' => $e->getMessage()], 500);
        }
    }

    private function calculateDiscountedPrice($price, $sale)
{
    return $price - ($price * $sale / 100);
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
            return response()->json(['message' => 'Đơn hàng không tồn tại hoặc không thuộc về người dùng.'], 404);
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
        $order = Order::with(['items.product', 'items.variant', 'shipping'])->find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        // {{ edit_1 }} Định dạng số điện thoại
        if ($order->shipping) {
            $order->shipping->phone = $this->formatPhoneNumber($order->shipping->phone);
        }
        // {{ edit_1 }}

        return response()->json([
            'message' => 'Đơn hàng đã được lấy thành công.',
            'order' => $order
        ], 200);
    }

    // {{ edit_2 }} Hàm định dạng số điện thoại
    private function formatPhoneNumber($phone)
    {
        return substr($phone, 0, 3) . '*****' . substr($phone, -2);
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
            ->with(['items.product', 'items.variant'])
            ->get();

        $ordersWithShipping = [];
        foreach ($orders as $order) {
            if ($order->shipping()->exists()) {
                $ordersWithShipping[] = $order; // Add the entire order with shipping info
            }
        }

        return response()->json([
            'message' => 'Danh sách đơn hàng có thông tin shipping.',
            'orders' => $ordersWithShipping
        ], 200);
    }

    public function showOrderByTrackingCode($tracking_code, $phone)
    {
        $order = Order::where('tracking_code', $tracking_code)
            ->with(['items.product', 'items.variant', 'shipping'])
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        if ($order->shipping && $order->shipping->phone !== $phone) {
            return response()->json(['message' => 'Số điện thoại không chính xác.'], 400);
        }

        return response()->json([
            'message' => 'Đơn hàng đã được lấy thành công.',
            'order' => $order
        ], 200);
    }
    private function restoreStock($order)
{
    foreach ($order->items as $item) {
        $variant = ProductVariant::find($item->variant_id);
        if ($variant) {
            $variant->stock_quantity += $item->quantity;
            $variant->save();
        }
    }
}
}
