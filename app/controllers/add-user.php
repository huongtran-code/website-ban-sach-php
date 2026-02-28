<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_POST['full_name']) && isset($_POST['email']) && isset($_POST['password'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $balance = isset($_POST['balance']) ? (float)$_POST['balance'] : 0;

    if (empty($full_name) || empty($email) || empty($password)) {
        header("Location: ../../pages/add-user.php?error=Vui lòng điền đầy đủ thông tin bắt buộc");
        exit;
    }

    if (strlen($password) < 6) {
        header("Location: ../../pages/add-user.php?error=Mật khẩu phải có ít nhất 6 ký tự");
        exit;
    }

    // Kiểm tra email đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: ../../pages/add-user.php?error=Email đã tồn tại");
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Thêm người dùng
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, balance) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$full_name, $email, $hashed_password, $phone, $address, $balance])) {
        header("Location: ../../pages/admin-users.php?success=Thêm người dùng thành công");
    } else {
        header("Location: ../../pages/add-user.php?error=Có lỗi xảy ra khi thêm người dùng");
    }
    exit;
} else {
    header("Location: ../../pages/add-user.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}





