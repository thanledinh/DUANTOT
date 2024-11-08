<!DOCTYPE html>
<html>
<head>
    <title>Mã OTP Đặt Lại Mật Khẩu</title>
    <style>
        /* Đặt font chữ chung */
        body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        /* Container tổng cho nội dung email */
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e0e0;
        }

        /* Tiêu đề lớn */
        h1 {
            color: #444;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
        }

        /* Đoạn văn bản mô tả */
        p {
            font-size: 16px;
            margin: 10px 0;
            text-align: center;
        }

        /* Mã OTP nổi bật */
        .otp-code {
            font-size: 28px;
            font-weight: bold;
            color: #00c2c7;
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border-radius: 5px;
            background-color: #f0fcfc;
            display: inline-block;
        }

        /* Chân trang */
        .footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-top: 20px;
        }

        /* Đường phân cách */
        hr {
            border: 0;
            height: 1px;
            background: #e0e0e0;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Xin chào,</h1>
        <p>Bạn đã yêu cầu đặt lại mật khẩu. Mã OTP của bạn là:</p>
        <div class="otp-code">{{ $otp }}</div>
        <p>Mã này sẽ hết hạn sau 5 phút.</p>
        <hr>
        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        <div class="footer">
            <p>Trân trọng,</p>
            <p>Đội ngũ hỗ trợ của chúng tôi</p>
        </div>
    </div>
</body>
</html>
