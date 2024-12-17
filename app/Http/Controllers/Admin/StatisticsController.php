<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exports\ProfitExport;
use Maatwebsite\Excel\Facades\Excel;

class StatisticsController extends Controller
{
    // Lấy số lượng đơn hàng theo tháng
    public function getOrdersByMonth($monthYear)
    {
        try {
            $month = Carbon::createFromFormat('Y-m', $monthYear);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }

        // Đếm đơn hàng theo tháng
        $ordersByMonth = Order::whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->count();

        return response()->json(['orders_by_month' => $ordersByMonth]);
    }

    // Lấy số lượng đơn hàng theo từng ngày trong tháng
    public function getDailyStatistics($monthYear)
    {
        try {
            $month = Carbon::createFromFormat('Y-m', $monthYear);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }

        // Dùng nhóm truy vấn thay vì vòng lặp
        $dailyStatistics = Order::select(DB::raw('DAY(created_at) as day, COUNT(*) as total'))
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->groupBy(DB::raw('DAY(created_at)'))
            ->pluck('total', 'day');

        // Điền các ngày không có đơn hàng (đặt giá trị 0)
        $daysInMonth = $month->daysInMonth;
        $result = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $result[$day] = $dailyStatistics->get($day, 0);
        }

        return response()->json($result);
    }

    // Lấy thống kê đơn hàng hàng tháng
    public function getMonthlyStatistics($year)
    {
        try {
            $year = (int)$year;
            if ($year <= 0) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid year format'], 400);
        }

        // Dùng nhóm truy vấn để thống kê theo tháng
        $monthlyStatistics = Order::select(DB::raw('MONTH(created_at) as month, COUNT(*) as total'))
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('total', 'month');

        // Điền các tháng không có đơn hàng (đặt giá trị 0)
        $result = [];
        for ($month = 1; $month <= 12; $month++) {
            $result[$month] = $monthlyStatistics->get($month, 0);
        }

        return response()->json($result);
    }

    // Lấy số lượng đơn hàng theo tuần
    public function getOrdersByWeek($weekStartDate)
    {
        try {
            $startDate = Carbon::parse($weekStartDate);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format'], 400);
        }

        $endDate = $startDate->copy()->addWeek()->subSecond(); // Kết thúc cuối tuần
        $ordersByWeek = Order::whereBetween('created_at', [$startDate, $endDate])->count();

        return response()->json(['orders_by_week' => $ordersByWeek]);
    }

    // Lấy số lượng đơn hàng trong năm
    public function getOrdersByYear($year)
    {
        try {
            $year = (int)$year;
            if ($year <= 0) {
                throw new \Exception();
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid year format'], 400);
        }

        $ordersByYear = Order::whereYear('created_at', $year)->count();
        return response()->json(['orders_by_year' => $ordersByYear]);
    }

    // Tổng số người dùng
    public function getTotalUsers()
    {
        $totalUsers = User::count();
        return response()->json(['total_users' => $totalUsers]);
    }

    // Tổng số đơn hàng
    public function getTotalOrders()
    {
        $totalOrders = Order::count();
        return response()->json(['total_orders' => $totalOrders]);
    }

    // Tổng số sản phẩm
    public function getTotalProducts()
    {
        $totalProducts = Product::count();
        return response()->json(['total_products' => $totalProducts]);
    }

    // Thống kê đơn hàng theo trạng thái
    public function getOrdersByStatus()
    {
        $orderCounts = Order::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json($orderCounts);
    }

    // Thống kê số lần sử dụng voucher
    public function getVoucherUsage()
    {
        $voucherUsage = DB::table('orders')
            ->select('id_promotion', DB::raw('COUNT(*) as total_used'))
            ->whereNotNull('id_promotion')
            ->groupBy('id_promotion')
            ->pluck('total_used', 'id_promotion');

        return response()->json($voucherUsage);
    }

    public function getTotalRevenue()
    {
        try {
            // Lấy tất cả đơn hàng đã giao thành công
            $orders = Order::where('status', 'Đã giao hàng')->get();
    
            $totalRevenue = 0;
    
            foreach ($orders as $order) {
                // Trừ 40.000 cho mỗi đơn hàng
                $orderRevenue = $order->total_price - 40000;
    
                // Cộng vào tổng doanh thu
                $totalRevenue += $orderRevenue;
            }
    
            return response()->json([
                'total_revenue' => $totalRevenue,
                'message' => 'Tổng doanh thu đã được tính thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi tính doanh thu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    public function exportProfitToExcel(Request $request)
    {
        try {
            // Lấy dữ liệu từ hàm calculateProfit với tham số thời gian
            $profitData = $this->calculateProfit($request)->getData(true);
    
            // Gọi Export với dữ liệu tính lợi nhuận
            return Excel::download(new ProfitExport($profitData['orders_details']), 'profit_report.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi xuất Excel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    

    public function calculateProfit(Request $request)
    {
        try {
            $fromDate = $request->input('from_date', now()->startOfMonth()); // Mặc định từ đầu tháng
            $toDate = $request->input('to_date', now()->endOfMonth()); // Mặc định đến cuối tháng
    
            $totalProfit = 0;
            $totalCostPrice = 0;
            $ordersDetails = [];
    
            // Lấy tất cả đơn hàng đã giao thành công trong khoảng thời gian
            $orders = Order::where('status', 'Đã giao hàng')
                ->whereBetween('created_at', [$fromDate, $toDate]) // Lọc theo ngày
                ->with(['items.variant', 'items.product', 'shipping'])
                ->get();
    
            foreach ($orders as $order) {
                $orderProfit = 0;
                $orderProducts = [];
    
                foreach ($order->items as $item) {
                    $variant = $item->variant;
                    $costPrice = $variant->cost_price ?? 0.00;
    
                    $itemProfit = $item->price - $costPrice;
                    $orderProfit += $itemProfit * $item->quantity;
                    $totalCostPrice += $costPrice * $item->quantity;
    
                    $orderProducts[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name ?? 'N/A',
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'cost_price' => $costPrice,
                        'sale_price' => $item->price,
                        'profit_per_item' => $itemProfit,
                    ];
                }
    
                $shippingCost = $order->shipping->shipping_cost ?? 0;


                // Nếu phí vận chuyển = 0, thì trừ phí vận chuyển vào lợi nhuận
                if ($shippingCost == 0.00) {
                    $orderProfit -= 40000;  // Giả sử phí vận chuyển cần trừ là 40k
                }

                
    
                $isFreeShipping = $shippingCost == 0;
                $totalProfit += $orderProfit;
    
                $ordersDetails[] = [
                    'order_id' => $order->id,
                    'order_code' => $order->id ?? 'N/A',
                    'total_order_profit' => $orderProfit,
                    'total_shipping_cost' => $shippingCost,
                    'is_free_shipping' => $isFreeShipping,
                    'products' => $orderProducts,
                    'order_date' => $order->created_at->setTimezone('Asia/Ho_Chi_Minh')->format('Y-m-d'), 
                ];
            }
    
            return response()->json([
                'total_profit' => $totalProfit,
                'total_cost_price' => $totalCostPrice,
                'orders_details' => $ordersDetails,
                'message' => 'Tổng lợi nhuận đã được tính thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi tính lợi nhuận',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
}
