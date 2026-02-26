<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: ../adminlogin.php");
    exit;
}

include "../db_conn.php";
include "func-user.php";

if (!isset($_POST['user_id']) || !isset($_POST['membership_level'])) {
    header("Location: ../admin-users.php?error=Thiếu thông tin");
    exit;
}

$user_id = (int)$_POST['user_id'];
$membership_level = $_POST['membership_level'];

// Validate membership level
$valid_levels = ['normal', 'silver', 'gold', 'diamond'];
if (!in_array($membership_level, $valid_levels)) {
    header("Location: ../admin-users.php?error=Hạng thành viên không hợp lệ");
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE users SET membership_level = ? WHERE id = ?");
    $stmt->execute([$membership_level, $user_id]);
    
    header("Location: ../admin-users.php?success=Đã cập nhật hạng thành viên thành công");
    exit;
} catch (PDOException $e) {
    header("Location: ../admin-users.php?error=Lỗi: " . urlencode($e->getMessage()));
    exit;
}




