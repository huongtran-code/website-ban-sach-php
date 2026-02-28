<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-mail.php";

if (isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    // Kiểm tra email có tồn tại không
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Tạo reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Lưu token vào database
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);
        
        // Tạo reset link (gửi qua email)
        $reset_link = "http://localhost/Online-Book-Store-By-using-PHP-and-MySQL-main/pages/reset-password.php?token=" . $token;
        
        // Gửi email đặt lại mật khẩu
        $sent = send_reset_password_email($conn, $email, $user['full_name'], $reset_link);
        
        if ($sent) {
            header("Location: ../../pages/forgot-password.php?success=" . urlencode("Link đặt lại mật khẩu đã được gửi đến email của bạn."));
        } else {
            // Fallback: thông báo kèm link (trường hợp server chưa cấu hình gửi mail)
            header("Location: ../../pages/forgot-password.php?success=" . urlencode("Không gửi được email tự động, vui lòng truy cập liên kết sau để đặt lại mật khẩu: " . $reset_link));
        }
    } else {
        header("Location: ../../pages/forgot-password.php?error=Email không tồn tại trong hệ thống");
    }
    exit;
} else {
    header("Location: ../../pages/forgot-password.php?error=Vui lòng nhập email");
    exit;
}

