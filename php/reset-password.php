<?php
session_start();
include "../db_conn.php";

if (isset($_POST['token']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $token = trim($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        header("Location: ../reset-password.php?token=$token&error=Mật khẩu xác nhận không khớp");
        exit;
    }
    
    if (strlen($password) < 6) {
        header("Location: ../reset-password.php?token=$token&error=Mật khẩu phải có ít nhất 6 ký tự");
        exit;
    }
    
    // Kiểm tra token
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Cập nhật mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
        
        header("Location: ../login.php?success=Đặt lại mật khẩu thành công! Vui lòng đăng nhập.");
    } else {
        header("Location: ../login.php?error=Token không hợp lệ hoặc đã hết hạn");
    }
    exit;
} else {
    header("Location: ../login.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}




