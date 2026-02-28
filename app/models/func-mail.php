<?php
// Hàm gửi email hệ thống (dùng mail() thuần, có thể thay bằng PHPMailer nếu cần)
include_once __DIR__ . "/func-settings.php";

if (!function_exists('send_system_email')) {
    function send_system_email(PDO $conn, string $toEmail, string $subject, string $htmlBody, string $altBody = ''): bool
    {
        $fromEmail = get_setting($conn, 'system_email', 'no-reply@example.com');
        $fromName  = get_setting($conn, 'system_email_name', 'Nhà Sách Online');

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: " . mb_encode_mimeheader($fromName, 'UTF-8') . " <{$fromEmail}>\r\n";

        $body = $htmlBody;
        if ($altBody !== '') {
            // Một số MTA không hỗ trợ multipart phức tạp, nên tạm dùng HTML là chính
            $body = $htmlBody . "<hr><pre>" . htmlspecialchars($altBody) . "</pre>";
        }

        // Lưu ý: hàm mail() cần cấu hình SMTP/sendmail trên server mới hoạt động thực tế.
        return (bool) @mail($toEmail, $subject, $body, $headers);
    }
}

if (!function_exists('send_reset_password_email')) {
    function send_reset_password_email(PDO $conn, string $toEmail, string $fullName, string $resetLink): bool
    {
        $subject = "[Nhà Sách Online] Đặt lại mật khẩu tài khoản";

        $html = '
            <p>Chào ' . htmlspecialchars($fullName) . ',</p>
            <p>Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản tại <strong>Nhà Sách Online</strong>.</p>
            <p>Vui lòng bấm vào nút bên dưới (hoặc copy link) để đặt lại mật khẩu (hiệu lực trong 24 giờ):</p>
            <p style="text-align:center;margin:24px 0;">
                <a href="' . htmlspecialchars($resetLink) . '" 
                   style="display:inline-block;padding:10px 18px;background:#e31837;color:#fff;text-decoration:none;border-radius:4px;">
                    ĐẶT LẠI MẬT KHẨU
                </a>
            </p>
            <p>Nếu bạn không yêu cầu, vui lòng bỏ qua email này.</p>
            <p>Trân trọng,<br>Nhà Sách Online</p>
        ';

        $alt = "Chào {$fullName},\n\n"
             . "Bạn có thể đặt lại mật khẩu bằng cách truy cập link sau (hiệu lực 24 giờ):\n"
             . $resetLink . "\n\n"
             . "Nếu bạn không yêu cầu, hãy bỏ qua email này.\n\n"
             . "Nhà Sách Online";

        return send_system_email($conn, $toEmail, $subject, $html, $alt);
    }
}

if (!function_exists('send_order_confirmation_email')) {
    function send_order_confirmation_email(PDO $conn, array $user, int $orderId): bool
    {
        $toEmail  = $user['email'] ?? '';
        $fullName = $user['full_name'] ?? 'Quý khách';

        if (empty($toEmail)) {
            return false;
        }

        // Lấy thông tin đơn hàng và chi tiết đơn
        $stmt = $conn->prepare("SELECT total_amount, status, payment_method, created_at FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return false;
        }

        $stmt = $conn->prepare("
            SELECT oi.quantity, oi.price, oi.book_type, b.title 
            FROM order_items oi 
            JOIN books b ON oi.book_id = b.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subject = "[Nhà Sách Online] Xác nhận đơn hàng #" . $orderId;

        $rowsHtml = '';
        $subtotal = 0;
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['price'];
            $subtotal += $lineTotal;
            $rowsHtml .= '<tr>'
                . '<td style="padding:6px 8px;border:1px solid #ddd;">' . htmlspecialchars($item['title']) . ' (' . htmlspecialchars($item['book_type']) . ')</td>'
                . '<td style="padding:6px 8px;border:1px solid #ddd;text-align:center;">' . (int)$item['quantity'] . '</td>'
                . '<td style="padding:6px 8px;border:1px solid #ddd;text-align:right;">' . number_format($item['price'], 0, ',', '.') . 'đ</td>'
                . '<td style="padding:6px 8px;border:1px solid #ddd;text-align:right;">' . number_format($lineTotal, 0, ',', '.') . 'đ</td>'
                . '</tr>';
        }

        $html = '
            <p>Chào ' . htmlspecialchars($fullName) . ',</p>
            <p>Cảm ơn bạn đã đặt hàng tại <strong>Nhà Sách Online</strong>.</p>
            <p>Thông tin đơn hàng của bạn (#' . $orderId . '):</p>
            <table style="border-collapse:collapse;width:100%;margin:12px 0;font-size:14px;">
                <thead>
                    <tr>
                        <th style="padding:6px 8px;border:1px solid #ddd;text-align:left;">Sách</th>
                        <th style="padding:6px 8px;border:1px solid #ddd;">SL</th>
                        <th style="padding:6px 8px;border:1px solid #ddd;">Đơn giá</th>
                        <th style="padding:6px 8px;border:1px solid #ddd;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>' . $rowsHtml . '</tbody>
            </table>
            <p><strong>Tạm tính (trước phí ship/giảm giá):</strong> ' . number_format($subtotal, 0, ',', '.') . 'đ</p>
            <p><strong>Tổng thanh toán:</strong> ' . number_format($order['total_amount'], 0, ',', '.') . 'đ</p>
            <p><strong>Phương thức thanh toán:</strong> ' . htmlspecialchars($order['payment_method']) . '</p>
            <p>Trạng thái hiện tại: <strong>' . htmlspecialchars($order['status']) . '</strong></p>
            <p>Bạn có thể xem chi tiết đơn hàng tại trang \"Đơn hàng của tôi\" sau khi đăng nhập.</p>
            <p>Trân trọng,<br>Nhà Sách Online</p>
        ';

        $alt = "Chào {$fullName},\n\n"
             . "Cảm ơn bạn đã đặt hàng tại Nhà Sách Online.\n"
             . "Mã đơn: #{$orderId}\n"
             . "Tổng thanh toán: " . number_format($order['total_amount'], 0, ',', '.') . "đ\n"
             . "Vui lòng đăng nhập để xem chi tiết đơn hàng.\n\n"
             . "Nhà Sách Online";

        return send_system_email($conn, $toEmail, $subject, $html, $alt);
    }
}

