<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class StatisticsController extends Controller
{
    // Phương thức lấy số lượng đơn hàng theo tháng
    public function getOrdersByMonth($monthYear)
    {
        // Kiểm tra định dạng tháng/năm
        try {
            $month = \Carbon\Carbon::createFromFormat('Y-m', $monthYear);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }

        // Đếm đơn hàng theo tháng
        $ordersByMonth = Order::whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->count();

        return response()->json(['orders_by_month' => $ordersByMonth]);
    }

    // Phương thức lấy thống kê hàng tháng cho năm được chỉ định
    public function getMonthlyStatistics($year)
    {
        $monthlyStatistics = [];

        // Thống kê số đơn hàng theo tháng cho năm được chỉ định
        for ($month = 1; $month <= 12; $month++) {
            $monthYear = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT);
            $response = $this->getOrdersByMonth($monthYear)->getData(); // Lấy số đơn hàng theo tháng

            // Kiểm tra và lưu số đơn hàng cho từng tháng
            $ordersByMonth = isset($response->orders_by_month) ? $response->orders_by_month : 0; // Default to 0 if no data is found

            // Lưu số đơn hàng cho từng tháng
            $monthlyStatistics[$month] = $ordersByMonth;
        }

        return response()->json($monthlyStatistics);
    }

    // Thêm các phương thức khác như tổng đơn hàng, thống kê theo tuần, v.v.
    public function getTotalOrders()
    {
        $totalOrders = Order::count(); // Fetch total orders
        return response()->json(['total_orders' => $totalOrders]); // Return as JSON
    }

    public function getOrdersByWeek($weekStartDate)
    {
        $ordersByWeek = Order::whereBetween('created_at', [
            $weekStartDate,
            \Carbon\Carbon::parse($weekStartDate)->addWeek()
        ])->count(); // Đếm đơn hàng theo tuần
        return response()->json(['orders_by_week' => $ordersByWeek]); // Trả về dưới dạng JSON
    }

    public function getOrdersByYear($year)
    {
        $ordersByYear = Order::whereYear('created_at', $year)->count();
        return response()->json(['orders_by_year' => $ordersByYear]);
    }

    public function getDailyStatistics($monthYear)
    {
        // Kiểm tra định dạng tháng/năm
        try {
            $month = \Carbon\Carbon::createFromFormat('Y-m', $monthYear);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }

        $dailyStatistics = [];

        // Thống kê số đơn hàng theo ngày cho tháng được chỉ định
        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            $ordersByDay = Order::whereDate('created_at', $date)->count(); // Đếm số đơn hàng theo ngày
            $dailyStatistics[$day] = $ordersByDay; // Lưu số đơn hàng cho từng ngày
        }

        return response()->json($dailyStatistics);
    }



    public function getTotalUsers()
    {
        $totalUsers = User::count(); // Fetch total users
        return response()->json(['total_users' => $totalUsers]); // Return as JSON
    }

    // tổng số sản phẩm
    public function getTotalProducts()
    {
        $totalProducts = Product::count(); // Đếm tổng số sản phẩm
        return response()->json(['total_products' => $totalProducts]); // Trả về dưới dạng JSON
    }

    //thống kê đơn hàng theo 4 trạng thái 
    public function getOrdersByStatus()
    {
        $statuses = ['Tiếp nhận', 'Đang vận chuyển', 'Đã giao hàng', 'Đã hủy'];
        $orderCounts = [];

        foreach ($statuses as $status) {
            $orderCounts[$status] = Order::where('status', $status)->count();
        }

        return response()->json($orderCounts);
    }

}
