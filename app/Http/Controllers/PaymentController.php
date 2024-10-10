<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function getPaymentInfo($orderId)
    {
        // Tìm đơn hàng theo ID
        $order = Order::find($orderId);

        // Kiểm tra nếu đơn hàng không tồn tại
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Tạo mã xác nhận giao dịch duy nhất (mã nội dung chuyển khoản)
        $transactionCode = Str::uuid(); // Sử dụng UUID cho mã giao dịch duy nhất

        // Thông tin mặc định cho giao dịch
        $bankId = '970422'; // Mã ngân hàng mặc định (Ví dụ: 970422)
        $accountNo = '0911990051'; // Số tài khoản mặc định
        $template = 'compact2'; // Template mặc định là compact2
        $accountName = urlencode('LE DINH THAN'); // Tên người nhận mặc định
        $amount = $order->total_price; // Số tiền thanh toán từ đơn hàng
        $addInfo = urlencode("Thanh toán #$transactionCode"); // Mã giao dịch

        // Tạo URL cho mã QR
        $qrLink = "https://img.vietqr.io/image/{$bankId}-{$accountNo}-{$template}.png?amount={$amount}&addInfo={$addInfo}&accountName={$accountName}";

        // Lưu thông tin thanh toán vào bảng payments
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'Bank Transfer',
            'amount' => $amount,
            'transaction_code' => $transactionCode, // Lưu mã giao dịch
            'payment_status' => 'pending', // Mặc định là đang chờ xử lý
        ]);

        // Gọi API Casso để lấy giao dịch gần nhất
        $transactionInfo = $this->getLatestTransaction($transactionCode);

        // So sánh mã giao dịch
        if ($transactionInfo && $transactionInfo['tid'] === $transactionCode) {
            // Cập nhật trạng thái thanh toán thành công
            $payment->update(['payment_status' => 'success']);
        }

        // Trả về JSON chứa thông tin mã QR
        return response()->json([
            'order_id' => $order->id,
            'qr_link' => $qrLink,
            'total_price' => $amount,
            'account_no' => $accountNo,
            'bank_id' => $bankId,
            'account_name' => $accountName,
            'transaction_code' => $transactionCode // Gửi mã giao dịch về frontend (nếu cần)
        ]);
        
    }

    public function getLatestTransaction($transactionCode)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://oauth.casso.vn/v2/transactions?fromDate=" . date('Y-m-d', strtotime('-7 days')),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Apikey " . env('CASSO_API_KEY'),
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return null; // Xử lý lỗi nếu cần
        }

        $data = json_decode($response, true);

        // Kiểm tra xem mảng 'data' có tồn tại không
        if (isset($data['data']) && isset($data['data']['records'])) {
            // Tìm giao dịch có mã giao dịch khớp
            foreach ($data['data']['records'] as $record) {
                if ($record['tid'] === $transactionCode) {
                    return $record; // Trả về giao dịch khớp
                }
            }
        } else {
            // Xử lý trường hợp không có dữ liệu
            return null; // Không tìm thấy giao dịch khớp
        }
    }
}
