<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['user_id']) && isset($_POST['full_name']) && isset($_POST['email'])) {
    $id = (int)$_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $balance = isset($_POST['balance']) ? (float)$_POST['balance'] : 0;
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($full_name) || empty($email)) {
        header("Location: ../edit-user.php?id=$id&error=Vui lòng điền đầy đủ thông tin bắt buộc");
        exit;
    }

    // Kiểm tra email đã tồn tại chưa (trừ chính user này)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        header("Location: ../edit-user.php?id=$id&error=Email đã được sử dụng bởi người dùng khác");
        exit;
    }

    // Nếu có mật khẩu mới, hash nó
    if (!empty($password)) {
        if (strlen($password) < 6) {
            header("Location: ../edit-user.php?id=$id&error=Mật khẩu phải có ít nhất 6 ký tự");
            exit;
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, phone = ?, address = ?, balance = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $hashed_password, $phone, $address, $balance, $id]);
    } else {
        // Không đổi mật khẩu
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, balance = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $address, $balance, $id]);
    }

    header("Location: ../admin-users.php?success=Cập nhật người dùng thành công");
    exit;
} else {
    header("Location: ../admin-users.php?error=Yêu cầu không hợp lệ");
    exit;
}





