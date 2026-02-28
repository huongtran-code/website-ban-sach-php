<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        header("Location: ../../pages/admin.php");
        exit;
    } else {
        header("Location: ../../pages/adminlogin.php?error=Email hoặc mật khẩu không đúng");
        exit;
    }
} else {
    header("Location: ../../pages/adminlogin.php?error=Vui lòng điền đầy đủ tất cả các trường");
    exit;
}
