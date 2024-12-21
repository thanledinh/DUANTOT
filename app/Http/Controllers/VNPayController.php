<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment; // Thêm mô hình Payment
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VNPayController extends Controller
{
    public function createPayment(Request $request)
    {

        $transactionCode = uniqid('txn_');
        // Tìm đơn hàng theo order_id
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Gán giá vào biến $vnp_Amount
        $vnp_Amount = $order->total_price * 100; // Số tiền (đơn vị VNĐ, nhân 100 để tính theo VNPAY)

        // Thông tin VNPAY
        $vnp_TmnCode = 'FOTS7Y02'; // Mã website tại VNPAY
        $vnp_HashSecret = 'GZ7MH82IROI43JZSPSSEOXEPLY5ZCPYP'; // Chuỗi bí mật
        $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'; // URL thanh toán VNPAY
        $vnp_Returnurl = 'http://localhost:5173/payment-return'; // URL phản hồi sau thanh toán

        $vnp_TxnRef = $order->id; // Mã đơn hàng
        $vnp_OrderInfo = 'Thanh toan don hang ' . $vnp_TxnRef;
        $vnp_OrderType = 'billpayment';
        $vnp_Locale = 'vn';
        $vnp_IpAddr = request()->ip();

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        // Mã hóa URL thanh toán
        ksort($inputData);
        $query = "";
        $hashdata = "";
        $i = 0;

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;

        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }


        // Lưu thông tin thanh toán vào cơ sở dữ liệu
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => 'VNPAY',
            'amount' => $vnp_Amount / 100, // Chuyển đổi về VNĐ
            'payment_status' => 'pending', // Mặc định là đang chờ xử lý
            'transaction_code' => '$transactionCode', // Không lưu giá trị null
            'bank_account' => null, // Cũng có thể cập nhật sau
            'transaction_id' => null, // Cũng có thể cập nhật sau
        ]);

        // Trả về URL thanh toán
        return response()->json($vnp_Url); // Trả về URL thanh toán
    }

    public function paymentReturn(Request $request)
    {
        // Lấy chuỗi bí mật từ tệp cấu hình
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $inputData = [];

        // Lấy các tham số từ phản hồi của VNPAY
        foreach ($request->all() as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }

        // Lấy mã bảo mật
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']); // Xóa mã bảo mật ra khỏi dữ liệu để tính toán

        // Sắp xếp các tham số và tạo chuỗi để tính toán mã bảo mật
        ksort($inputData);
        $hashData = "";

        foreach ($inputData as $key => $value) {
            $hashData .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        $hashData = rtrim($hashData, '&'); // Loại bỏ ký tự '&' cuối cùng

        // Tính toán mã bảo mật
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // Kiểm tra mã bảo mật và mã phản hồi
        if ($secureHash === $vnp_SecureHash) {
            if ($inputData['vnp_ResponseCode'] === '00') {
                // Thanh toán thành công
                $payment = Payment::where('order_id', $inputData['vnp_TxnRef'])->first();
                if ($payment) {
                    // Cập nhật thông tin thanh toán
                    $payment->update([
                        'payment_status' => 'success',
                        'transaction_id' => $inputData['vnp_TransactionNo'], // Mã giao dịch từ VNPAY
                        'transaction_code' => $inputData['vnp_TransactionCode'], // Mã giao dịch
                    ]);
                }
                return view('payment.success'); // Hiển thị trang thành công
            } else {
                // Thanh toán không thành công
                return view('payment.error', ['message' => 'Thanh toán không thành công. Mã lỗi: ' . $inputData['vnp_ResponseCode']]);
            }
        } else {
            // Xác thực không thành công
            return view('payment.error', ['message' => 'Dữ liệu không hợp lệ.']);
        }
    }

        // Phương thức cập nhật trạng thái thanh toán
        public function updatePaymentStatus(Request $request)
        {
            // Validate the incoming request
            $request->validate([
                'transaction_id' => 'required|string',
                // 'transaction_code' => 'required|string', // Removed this line
                'order_id' => 'required|integer',
                'payment_status' => 'required|string',
            ]);
    
            // Find the payment record
            $payment = Payment::where('order_id', $request->order_id)->first();
    
            if (!$payment) {
                return response()->json(['error' => 'Payment not found'], 404);
            }
    
            // Update the payment record
            $payment->update([
                'transaction_id' => $request->transaction_id,
                // 'transaction_code' => $request->transaction_code, // Removed this line
                'payment_status' => $request->payment_status,
            ]);
    
            // Update the corresponding order's payment method
            $order = Order::find($request->order_id);
            if ($order) {
                $order->update([
                    'payment_method' => 'VNPAY', // Set payment method to VNPAY
                    'status' => 'Tiếp nhận', // Set status to paid
                ]);
            }
    
        return response()->json(['message' => 'Payment status updated successfully']);
    }

    public function refundPayment(Request $request)
    {
        // Lấy thông tin cấu hình VNPay
        $vnp_TmnCode = 'FOTS7Y02'; // Mã website tại VNPay
        $vnp_HashSecret = 'GZ7MH82IROI43JZSPSSEOXEPLY5ZCPYP'; // Chuỗi bí mật VNPay

        // Validate input
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id'
        ]);

        $orderId = $request->order_id;

        // Tìm đơn hàng
        $order = Order::find($orderId);
        if (!$order) {
            Log::warning('Order not found for refund', ['order_id' => $orderId]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Tìm thanh toán thành công liên quan đến đơn hàng
        $payment = Payment::where('order_id', $orderId)
            ->where('payment_method', 'VNPAY')
            ->where('payment_status', 'success')
            ->first();

        if (!$payment) {
            Log::warning('No successful payment found for refund', ['order_id' => $orderId]);
            return response()->json(['error' => 'No successful payment found for this order'], 400);
        }

        // Tính toán số tiền hoàn
        $refundAmount = $this->calculateRefundAmount($order);
        if ($refundAmount <= 0) {
            Log::warning('Invalid refund amount', ['order_id' => $orderId, 'refund_amount' => $refundAmount]);
            return response()->json(['error' => 'Refund amount must be greater than 0'], 400);
        }

        // Chuẩn bị dữ liệu gửi đến VNPay
        $inputData = $this->prepareVNPayRefundRequest($payment->transaction_id, $refundAmount, $vnp_TmnCode, $vnp_HashSecret);

        // Gửi yêu cầu hoàn tiền
        try {
            Log::info('Sending refund request to VNPay', ['input_data' => $inputData]);
            $response = $this->sendVNPayRequest($inputData);
            $result = json_decode($response, true);

            // Log phản hồi
            Log::info('VNPay Refund Response', ['response' => $result]);

            // Xử lý kết quả trả về
            if ($result['RspCode'] === '00') {
                $order->update(['status' => 'refunded']);
                $payment->update(['payment_status' => 'refunded']);
                Log::info('Refund successful', ['order_id' => $orderId, 'refund_amount' => $refundAmount]);
                return response()->json([
                    'message' => 'Refund successful',
                    'refund_amount' => $refundAmount
                ], 200);
            } else {
                Log::error('Refund failed', ['order_id' => $orderId, 'error_message' => $result['Message']]);
                return response()->json(['error' => 'Refund failed: ' . $result['Message']], 400);
            }
        } catch (\Exception $e) {
            Log::error('VNPay Refund Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tính toán số tiền hoàn lại.
     */
    private function calculateRefundAmount($order)
    {
        $promotionAmount = $order->promotion_amount ?? 0;
        $totalPrice = $order->total_price;

        return max($totalPrice - $promotionAmount - 40000, 0);
    }

    /**
     * Chuẩn bị dữ liệu gửi hoàn tiền đến VNPay.
     */
    private function prepareVNPayRefundRequest($transactionId, $refundAmount, $vnp_TmnCode, $vnp_HashSecret)
    {
        $vnp_RequestId = uniqid();
        $vnp_Amount = $refundAmount * 100;
        $vnp_CreateDate = date('YmdHis');
        $vnp_IpAddr = request()->ip();

        $inputData = [
            'vnp_RequestId' => $vnp_RequestId,
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'refund',
            'vnp_TmnCode' => $vnp_TmnCode,
            'vnp_TransactionType' => '02',
            'vnp_TxnRef' => $transactionId,
            'vnp_Amount' => $vnp_Amount,
            'vnp_OrderInfo' => 'Refund for order #' . $transactionId,
            'vnp_TransactionDate' => $vnp_CreateDate,
            'vnp_CreateBy' => 'admin', // Hoặc tên người dùng thực hiện
            'vnp_CreateDate' => $vnp_CreateDate,
            'vnp_IpAddr' => $vnp_IpAddr,
        ];

        // Tạo SecureHash
        $hashData = implode('|', $inputData);
        $inputData['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // Log request
        Log::info('VNPay Refund Request', ['request_data' => $inputData]);

        return $inputData;
    }

    private function sendVNPayRequest($inputData)
    {
        $url = 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($inputData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new \Exception('CURL Error: ' . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            throw new \Exception('VNPay returned HTTP code ' . $httpCode);
        }

        curl_close($curl);

        return $response;
    }
    
}
    

