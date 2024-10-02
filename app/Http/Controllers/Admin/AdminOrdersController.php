<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
class AdminOrdersController extends Controller
{
    // show tất cả  đơn hàng của tất cả người dùng 
    public function index()
    {
        $orders = Order::with(['items.product', 'items.variant'])->get();
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

}
