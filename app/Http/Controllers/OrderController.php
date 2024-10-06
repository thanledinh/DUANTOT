<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str; 

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipping;
use Illuminate\Http\Request;


class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Lấy thông tin người dùng từ token đã xác thực
        $user = $request->user(); // JWT tự động lấy user từ token

        if (!$user) {
            return response()->json(['message' => 'Bạn cần phải đăng nhập để xem đơn hàng.'], 401);
        }

        // Lấy danh sách đơn hàng của người dùng
        $orders = Order::where('user_id', $user->id)
            ->with('items.product', 'items.variant')
            ->get();

        // Tìm đơn hàng dựa trên ID nếu có
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
    // Validate dữ liệu từ phía request
    $request->validate([
        'id_promotion' => 'nullable|integer',
        'total_price' => 'required|numeric',
        'payment_method' => 'nullable|string',
        'sale' => 'nullable|numeric',
        'note' => 'nullable|string',
        'items' => 'required|array',
        'items.*.product_id' => 'required|integer|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.price' => 'required|numeric|min:0',
        'items.*.variant_id' => 'required|integer|min:1',
    ]);

    try {
        // Tạo đối tượng đơn hàng mới
        $order = new Order();

        // Kiểm tra nếu người dùng đã đăng nhập, lưu `user_id`
        if (auth()->guard('api')->check()) {
            $order->user_id = auth()->guard('api')->id();
        } else {
            $order->user_id = null; // Đảm bảo user_id không được gán nếu người dùng chưa đăng nhập
        }

        // Tạo mã theo dõi đơn hàng (10 ký tự ngẫu nhiên)
        $order->tracking_code = strtoupper(Str::random(10)); // VD: 10 ký tự chữ hoa ngẫu nhiên

        $order->id_promotion = $request->id_promotion ?? null;
        $order->order_date = now(); // Tự động thêm ngày hiện tại
        $order->total_price = $request->total_price;
        $order->status = 'pending'; // Trạng thái mặc định là pending khi tạo đơn hàng
        $order->payment_method = $request->payment_method ?? null;
        $order->sale = $request->sale ?? 0;
        $order->note = $request->note ?? null;
        $order->save();

        // Lưu các mục trong đơn hàng
        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'variant_id' => $item['variant_id'],
                'price' => $item['price'],
            ]);
        }

        // Trả về phản hồi thành công
        return response()->json([
            'message' => 'Đơn hàng đã được tạo thành công.',
            'order' => $order,
            'tracking_code' => $order->tracking_code // Trả mã theo dõi về phản hồi cho người dùng
        ], 201);
    } catch (\Exception $e) {
        // Bắt lỗi và trả về thông báo
        return response()->json([
            'message' => 'Đã xảy ra lỗi khi tạo đơn hàng.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function update(Request $request, $id)
    {
        // Lấy thông tin người dùng từ token
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Lỗi: Người dùng không tồn tại.'], 401);
        }

        // Tìm đơn hàng dựa trên user_id và id đơn hàng
        $order = Order::where('user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại hoặc không thuộc về người dùng.'], 404);
        }

        if ($order->status == 'processed' || $order->status == 'completed') {
            return response()->json(['message' => 'Không thể thay đổi trạng thái của đơn hàng đã được xử lý hoặc hoàn thành.'], 403);
        }

        $request->validate([
            'status' => 'nullable|string|in:pending,processed,completed,canceled',
            'note' => 'nullable|string',
        ]);

        try {
            $order->update(array_filter([
                'status' => $request->status,
                'note' => $request->note,
                'payment_method' => $request->payment_method === 'cash_on_delivery' ? 'cash_on_delivery' : $order->payment_method, // Cập nhật payment_method
            ]));

            // Nếu payment_method là "cash_on_delivery", cập nhật trạng thái
            if ($request->payment_method === 'cash_on_delivery') {
                $order->status = 'confirmed'; // Cập nhật trạng thái
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
        // Kiểm tra xem đơn hàng có tồn tại không
        $order = Order::find($order_id);
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại.'], 404);
        }

        // Kiểm tra quyền truy cập của người dùng
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền xóa đơn hàng này.'], 403);
        }

        // Xóa đơn hàng
        $order->delete();

        return response()->json(['message' => 'Đơn hàng đã được xóa thành công.'], 200);
    }

    //show order theo id
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

    // show đơn hàng có trạng thái pending
    public function showPendingOrder()
    {
        $orders = Order::where('status', 'pending')
            ->with(['items.product', 'items.variant'])
            ->get();

        return response()->json([
            'message' => 'Đơn hàng đã được lấy thành công.',
            'orders' => $orders
        ], 200);
    }
}
