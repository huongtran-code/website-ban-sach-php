<?php
session_start();
include "../db_conn.php";
include "func-user.php";

if (isset($_POST['full_name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($password)) {
        header("Location: ../register.php?error=Vui lòng điền đầy đủ thông tin");
        exit;
    }

    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=Mật khẩu xác nhận không khớp");
        exit;
    }

    if (strlen($password) < 6) {
        header("Location: ../register.php?error=Mật khẩu phải có ít nhất 6 ký tự");
        exit;
    }

    $existing = get_user_by_email($conn, $email);
    if ($existing) {
        header("Location: ../register.php?error=Email này đã được sử dụng");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$full_name, $email, $phone, $hashed_password]);

    $user_id = $conn->lastInsertId();
    $_SESSION['customer_id'] = $user_id;
    $_SESSION['customer_name'] = $full_name;
    $_SESSION['customer_email'] = $email;

    header("Location: ../index.php");
    exit;
} else {
    header("Location: ../register.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}
