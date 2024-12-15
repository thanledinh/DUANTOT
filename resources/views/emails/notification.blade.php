<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo mới</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Banner */
        .banner {
            position: relative;
            background: linear-gradient(135deg, #ff7e5f, #feb47b);
            color: #fff;
            text-align: center;
            padding: 30px 20px;
            border-radius: 10px 10px 0 0;
        }

        .banner h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .banner p {
            font-size: 16px;
            margin: 10px 0 0;
        }

        .banner-icon {
            font-size: 50px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Nội dung */
        .content {
            padding: 20px;
        }

        h2 {
            color: #ff7e5f;
            margin-bottom: 15px;
        }

        p {
            font-size: 16px;
            margin: 10px 0;
        }

        /* Call-to-action */
        .cta {
            text-align: center;
            margin-top: 20px;
        }

        .cta a {
            display: inline-block;
            padding: 12px 25px;
            background: #ff7e5f;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }

        .cta a:hover {
            background: #e7634d;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Banner -->
        <div class="banner">
            <div class="banner-icon">🔔</div>
            <h1>Thông báo quan trọng</h1>
            <p>Hãy kiểm tra nội dung mới nhất ngay bây giờ!</p>
        </div>

        <!-- Nội dung -->
        <div class="content">
            <h2>Thông tin chi tiết</h2>
            <p>{{ $messageContent }}</p>
        </div>

        <!-- Call-to-action -->
        <div class="cta">
            <a href="https://petkorner.shop">Xem ngay</a>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi.</p>
            <p>Hỗ trợ khách hàng: <a href="mailto:petkorner.shop">petkorner.shop</a></p>
        </div>
    </div>
</body>

</html>
