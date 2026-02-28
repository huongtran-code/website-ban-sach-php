<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../../pages/admin-users.php?success=Xóa người dùng thành công");
    exit;
} else {
    header("Location: ../../pages/admin-users.php?error=ID không hợp lệ");
    exit;
}
