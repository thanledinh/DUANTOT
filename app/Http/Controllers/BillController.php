<?php
namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
   // Phương thức GET để lấy tất cả hóa đơn
public function getAllBills()
{
    // Lấy tất cả hóa đơn từ cơ sở dữ liệu
    $bills = DB::table('orders')
        ->leftJoin('users', 'orders.user_id', '=', 'users.id')
        ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
        ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
        ->select(
            'orders.id as order_id',
            'orders.order_date',
            'orders.total_price',
            'orders.payment_method',
            'orders.status',
            'users.username',
            'users.email',
            'users.address',
            'users.phone_number',
            'products.name as product_name',
            'order_items.quantity',
            'order_items.price'
        )
        ->get();

    // Kiểm tra nếu không có dữ liệu
    if ($bills->isEmpty()) {
        return response()->json(['message' => 'No orders found'], 404);
    }

    // Trả về tất cả hóa đơn dưới dạng JSON
    return response()->json($bills);
}

    // Phương thức POST để xuất hóa đơn dưới dạng PDF
   // Phương thức POST để xuất hóa đơn dưới dạng PDF
   public function exportBill($orderId)
   {
       // Kiểm tra nếu không có order_id trong yêu cầu
       if (!$orderId) {
           return response()->json(['message' => 'Order ID is required'], 400);
       }
   
       // Lấy thông tin hóa đơn từ cơ sở dữ liệu
       $bill = DB::table('orders')
           ->leftJoin('users', 'orders.user_id', '=', 'users.id')
           ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
           ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
           ->where('orders.id', $orderId)  // Lọc theo order_id
           ->select(
               'orders.id as order_id',
               'orders.order_date',
               'orders.total_price',
               'orders.payment_method',
               'orders.status',
               'users.username',
               'users.email',
               'users.address',
               'users.phone_number',
               'products.name as product_name',
               'order_items.quantity',
               'order_items.price'
           )
           ->get();
   
       // Kiểm tra nếu không có dữ liệu
       if ($bill->isEmpty()) {
           return response()->json(['message' => 'Order not found'], 404);
       }
   
       // Lấy phần tử đầu tiên của hóa đơn
       $order = $bill->first();
   
       // Tạo nội dung HTML cho hóa đơn
       $html = view('pdf.bill', compact('order', 'bill'))->render();
   
       // Khởi tạo DomPDF
       $options = new Options();
       $options->set('isHtml5ParserEnabled', true);
       $options->set('isPhpEnabled', true);
       $dompdf = new Dompdf($options);
   
       // Tải nội dung HTML vào DomPDF
       $dompdf->loadHtml($html);
   
       // Đặt kích thước giấy và định hướng (A4, Portrait)
       $dompdf->setPaper('A4', 'portrait');
   
       // Chuyển đổi HTML thành PDF
       $dompdf->render();
   
       // Xuất PDF và gửi file cho người dùng
       return $dompdf->stream('bill_' . $order->order_id . '.pdf');
   }
   

}
