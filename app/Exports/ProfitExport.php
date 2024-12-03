<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ProfitExport implements FromCollection, WithHeadings
{
    protected $ordersDetails;

    public function __construct($ordersDetails)
    {
        $this->ordersDetails = $ordersDetails; // Nhận dữ liệu orders từ controller
    }

    public function collection()
    {
        $exportData = [];

        foreach ($this->ordersDetails as $order) {
            foreach ($order['products'] as $product) {
                $exportData[] = [
                    'order_id' => $order['order_id'],
                    'order_code' => $order['order_code'],
                    'total_order_profit' => $order['total_order_profit'],
                    'total_shipping_cost' => $order['total_shipping_cost'],
                    'is_free_shipping' => $order['is_free_shipping'] ? 'Yes' : 'No',
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'variant_id' => $product['variant_id'],
                    'quantity' => $product['quantity'],
                    'cost_price' => $product['cost_price'],
                    'sale_price' => $product['sale_price'],
                    'profit_per_item' => $product['profit_per_item'],
                ];
            }
        }

        return collect($exportData);
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Order Code',
            'Total Order Profit',
            'Total Shipping Cost',
            'Is Free Shipping',
            'Product ID',
            'Product Name',
            'Variant ID',
            'Quantity',
            'Cost Price',
            'Sale Price',
            'Profit Per Item',
        ];
    }
}
