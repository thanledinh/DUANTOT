<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderStatusUpdated;

class AdminOrdersController extends Controller
{
    // show tất cả đơn hàng của tất cả người dùng, bao gồm thông tin shipping và payment
    public function index()
    {
        $orders = Order::with(['items.product', 'items.variant', 'shipping', 'payment'])
            ->where('status', '!=', 'pending') // Loại bỏ các đơn hàng có trạng thái pending
            ->orderBy('created_at', 'desc') // Sắp xếp theo ngày tạo giảm dần
            ->get();

        return response()->json([
            'message' => 'Đơn hàng đã được lấy thành công.',
            'orders' => $orders
        ], 200);
    }


    // show đơn hàng theo trang và số lượng ví dụ pagesize = 10, page = 1
    public function show($pageSize, $page)
    {
        $orders = Order::with(['items.product', 'items.variant'])->skip($pageSize * ($page - 1))->take($pageSize)->get();
        return response()->json([
            'message' => 'Đơn hàng đã được lấy thành công.',
            'orders' => $orders
        ], 200);
    }

    public function updateMultiple(Request $request)
    {
        // Định nghĩa danh sách trạng thái hợp lệ
        $validStatuses = ['processing', 'Tiếp nhận', 'Đang vận chuyển', 'Đã giao hàng', 'Đã hủy', 'Đã trả hàng'];

        // Xác thực yêu cầu
        $request->validate([
            'status' => 'required|string|in:' . implode(',', $validStatuses),
            'ids' => 'required|array', // Đảm bảo ids là một mảng
            'ids.*' => 'integer|exists:orders,id' // Mỗi id phải là số nguyên và tồn tại trong bảng orders
        ]);

        $status = $request->status;
        $ids = $request->ids;

        // Cập nhật trạng thái cho tất cả các đơn hàng
        Order::whereIn('id', $ids)->update(['status' => $status]);

        return response()->json([
            'message' => 'Trạng thái của các đơn hàng đã được cập nhật thành công.',
            'updated_ids' => $ids
        ], 200);
    }

    public function updateStatusOrder(Request $request, $id)
    {
        // Định nghĩa danh sách trạng thái hợp lệ và thứ tự của chúng
        $validStatuses = [
            'processing' => 0,
            'Tiếp nhận' => 1,
            'Đang vận chuyển' => 2,
            'Đã giao hàng' => 3,
            'Đã hủy' => 4
        ];

        // Xác thực yêu cầu
        $request->validate([
            'status' => 'required|string|in:' . implode(',', array_keys($validStatuses)),
        ]);

        // Lấy đơn hàng theo ID
        $order = Order::find($id);

        if ($order) {
            $shipping = $order->shipping;
            $shippingEmail = $shipping->email;

            // Kiểm tra xem email có tồn tại không
            if (!$shippingEmail) {
                return response()->json([
                    'message' => 'Email trong bảng Shipping không tồn tại.',
                ], 404);
            }

            // Kiểm tra xem trạng thái hiện tại có thể cập nhật không
            if (array_key_exists($order->status, $validStatuses)) {
                $currentStatusIndex = $validStatuses[$order->status];
                $newStatusIndex = $validStatuses[$request->status];

                // Kiểm tra trường hợp không hợp lệ giữa "Đã giao hàng" và "Đã hủy"
                if (
                    ($order->status === 'Đã giao hàng' && $request->status === 'Đã hủy') ||
                    ($order->status === 'Đã hủy' && $request->status === 'Đã giao hàng')
                ) {
                    return response()->json([
                        'message' => 'Không thể cập nhật từ "Đã giao hàng" lên "Đã hủy" hoặc từ "Đã hủy" lên "Đã giao hàng".',
                        'current_status' => $order->status,
                        'new_status' => $request->status
                    ], 400);
                }

                // Kiểm tra xem có lùi bậc không
                if ($currentStatusIndex > $newStatusIndex) {
                    $timeSinceUpdate = Carbon::parse($order->updated_at)->diffInMinutes(now());
                    // Kiểm tra nếu đã vượt quá 2 phút
                    if ($timeSinceUpdate > 2) {
                        return response()->json([
                            'message' => 'Không thể cập nhật trạng thái cho đơn hàng sau 2 phút.',
                            'current_status' => $order->status,
                            'new_status' => $request->status
                        ], 400);
                    }

                    // Trường hợp đặc biệt: trạng thái hiện tại là 4, cho phép quay về 0 hoặc 1
                    if ($currentStatusIndex === 4 && in_array($newStatusIndex, [0, 1])) {
                        $order->status = $request->status;
                        $order->save();
                        return response()->json([
                            'message' => 'Trạng thái của đơn hàng đã được cập nhật thành công.',
                            'order_id' => $order->id,
                            'new_status' => $order->status
                        ], 200);
                    }

                    // Cho phép lùi lại đúng 1 bậc trạng thái
                    if ($currentStatusIndex - 1 === $newStatusIndex) {
                        $order->status = $request->status;
                        $order->save();
                        return response()->json([
                            'message' => 'Trạng thái của đơn hàng đã được cập nhật thành công.',
                            'order_id' => $order->id,
                            'new_status' => $order->status
                        ], 200);
                    }

                    // Trường hợp không hợp lệ
                    return response()->json([
                        'message' => 'Chỉ có thể lùi lại 1 bậc trạng thái trong vòng 2 phút.',
                        'current_status' => $order->status,
                        'new_status' => $request->status
                    ], 400);
                }

                // Kiểm tra xem có nhảy bậc không
                if (($currentStatusIndex === 0 || $currentStatusIndex === 1 || $currentStatusIndex === 2) && $newStatusIndex === 4) {
                    $order->status = $request->status;
                    $order->save();

                    // Gửi email thông báo sau khi cập nhật
                    Mail::to($shippingEmail)->send(new OrderStatusUpdated($order, $shipping));

                    return response()->json([
                        'message' => 'Trạng thái của đơn hàng đã được cập nhật thành công.',
                        'order_id' => $order->id,
                        'new_status' => $order->status
                    ], 200);
                }

                // chỉ cho cập nhật mỗi lần lên 1 bậc
                if ($newStatusIndex > $currentStatusIndex + 1) {
                    return response()->json([
                        'message' => 'Không thể cập nhật trạng thái cho đơn hàng. Chỉ được cập nhật lên 1 bậc trạng thái',
                        'current_status' => $order->status,
                        'new_status' => $request->status
                    ], 400);
                }
            }

            // Cập nhật trạng thái mới
            $order->status = $request->status;
            $order->save();

            // Gửi email thông báo sau khi cập nhật
            Mail::to($shippingEmail)->send(new OrderStatusUpdated($order, $shipping));

            return response()->json([
                'message' => 'Trạng thái của đơn hàng đã được cập nhật thành công.',
                'order_id' => $order->id,
                'new_status' => $order->status
            ], 200);
        } else {
            return response()->json([
                'message' => 'Đơn hàng không tồn tại.',
            ], 404);
        }
    }






}
