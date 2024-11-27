<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ProfitExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use App\Models\Order;


class ProfitController extends Controller
{
    public function exportProfitToExcel()
    {
        try {
            // Lấy dữ liệu từ hàm calculateProfit
            $profitData = $this->calculateProfit()->getData(true);

            // Gọi Export với dữ liệu tính lợi nhuận
            return Excel::download(new ProfitExport($profitData['orders_details']), 'profit_report.xlsx');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi xuất Excel',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function calculateProfit()
    {
        try {
            $totalProfit = 0;
            $totalCostPrice = 0; 
            $ordersDetails = [];

            $orders = Order::where('status', 'Đã giao hàng')
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
                $orderProfit -= $shippingCost;
                $isFreeShipping = $shippingCost == 0;

                $totalProfit += $orderProfit;

                $ordersDetails[] = [
                    'order_id' => $order->id,
                    'order_code' => $order->id ?? 'N/A',
                    'total_order_profit' => $orderProfit,
                    'total_shipping_cost' => $shippingCost,
                    'is_free_shipping' => $isFreeShipping,
                    'products' => $orderProducts,
                ];
            }

            return response()->json([
                'total_profit' => $totalProfit,
                'total_cost_price' => $totalCostPrice,
                'orders_details' => $ordersDetails,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đã xảy ra lỗi khi tính lợi nhuận',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
