<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-user.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: ../../pages/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['ban','unban'])) {
    header("Location: ../../pages/admin-users.php?error=Yêu cầu không hợp lệ");
    exit;
}

try {
    if ($action === 'ban') {
        $reason = isset($_POST['ban_reason']) ? trim($_POST['ban_reason']) : null;
        ban_user($conn, $id, $reason);
        header("Location: ../../pages/admin-users.php?success=Đã khóa tài khoản người dùng");
        exit;
    } else {
        unban_user($conn, $id);
        header("Location: ../../pages/admin-users.php?success=Đã mở khóa tài khoản người dùng");
        exit;
    }
} catch (Exception $e) {
    header("Location: ../../pages/admin-users.php?error=" . urlencode("Lỗi: " . $e->getMessage()));
    exit;
}



