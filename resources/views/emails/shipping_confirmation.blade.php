<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận vận chuyển</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f7f9fc;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #ddd;
        }

        h1 {
            font-size: 28px;
            color: #0056b3;
            text-align: center;
            margin-bottom: 15px;
        }

        h2 {
            font-size: 22px;
            color: #007bff;
            border-bottom: 2px solid #e3e3e3;
            padding-bottom: 5px;
            margin-top: 30px;
        }

        p {
            font-size: 16px;
            margin: 10px 0;
            text-align: center;
        }

        .order-details {
            background: linear-gradient(135deg, #e3f6f5, #f0fdfa);
            padding: 15px;
            border-radius: 8px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: left;
            margin-bottom: 20px;
        }

        .order-details p {
            font-size: 16px;
            margin: 8px 0;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
            padding: 12px;
            text-align: center;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            font-size: 14px;
            color: #555;
            text-align: center;
            margin-top: 20px;
        }

        .highlight {
            color: #ff6f61;
            font-weight: bold;
        }

        .cta {
            text-align: center;
            margin-top: 20px;
        }

        .cta a {
            display: inline-block;
            padding: 12px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        }

        .cta a:hover {
            background-color: #0056b3;
        }

        hr {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Xác nhận vận chuyển</h1>
        <p>Xin chào <span class="highlight">{{ $shipping->full_name }}</span>,</p>
        <p>Mã đơn hàng của bạn là: {{ $order->tracking_code }}</p>

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

        <h2>Thông tin đơn hàng</h2>
        <table>
            <tr>
                <th>Ngày đặt hàng</th>
                <th>Tổng giá</th>
                <th>Trạng thái</th>
                <th>Phương thức thanh toán</th>
            </tr>
            <tr>
                <td>{{ $order->order_date }}</td>
                <td>{{ number_format($order->total_price, 0, ',', '.') }} VNĐ</td>
                <td>{{ $order->status }}</td>
                <p>Mã đơn hàng của bạn là: {{ $order->tracking_code }}</p>

                <td>{{ $order->payment_method }}</td>
            </tr>
        </table>

        <h2>Chi tiết sản phẩm</h2>
        <table>
            <tr>
                <th>Tên sản phẩm</th>
                <th>Loại</th>
                <th>Số lượng</th>
                <th>Giá</th>
            </tr>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->variant->type }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->price, 0, ',', '.') }} VNĐ</td>
            </tr>
            @endforeach
        </table>


        <hr>

        <div class="footer">
            <p>Trân trọng,</p>
            <p>Đội ngũ hỗ trợ khách hàng</p>
        </div>
    </div>
</body>

</html>
