<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận vận chuyển</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        .order-details {
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Xác nhận vận chuyển</h1>
        <p>Xin chào {{ $shipping->full_name }},</p>
        <p>Cảm ơn bạn đã đặt hàng tại cửa hàng của chúng tôi. Dưới đây là thông tin vận chuyển của bạn:</p>

        <div class="order-details">
            <p><strong>Địa chỉ giao hàng:</strong> {{ $shipping->shipping_address }}</p>
            <p><strong>Thành phố:</strong> {{ $shipping->city }}</p>
            <p><strong>Quận/Huyện:</strong> {{ $shipping->district }}</p>
            <p><strong>Phường/Xã:</strong> {{ $shipping->ward }}</p>
            <p><strong>Số điện thoại:</strong> {{ $shipping->phone }}</p>
            <p><strong>Phương thức vận chuyển:</strong> {{ $shipping->shipping_method }}</p>
            <p><strong>Chi phí vận chuyển:</strong> {{ number_format($shipping->shipping_cost, 0, ',', '.') }} VNĐ</p>
            <p><strong>Trạng thái vận chuyển:</strong> {{ $shipping->shipping_status }}</p>
        </div>

        <p>Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của chúng tôi!</p>

        <div class="footer">
            <p>Trân trọng,</p>
            <p>Đội ngũ hỗ trợ khách hàng</p>
        </div>
    </div>
</body>
</html>