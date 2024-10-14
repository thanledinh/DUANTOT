<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class AdminOrdersController extends Controller
{
    // show tất cả đơn hàng của tất cả người dùng, bao gồm thông tin shipping và payment
    public function index()
    {
        $orders = Order::with(['items.product', 'items.variant', 'shipping', 'payment']) // Include shipping and payment information
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

    // chỉnh sửa trạng thái nhiều đơn hàng
    public function updateMultiple(Request $request)
    {
        // Định nghĩa danh sách trạng thái hợp lệ
        $validStatuses = ['Đang vận chuyển', 'Đã giao hàng', 'Đã hủy', 'Đã trả hàng'];

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
}
