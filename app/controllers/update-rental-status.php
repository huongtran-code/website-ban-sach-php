<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: ../../pages/login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = $_GET['status'] ?? '';

if (!$id || !in_array($status, ['active','expired','cancelled'])) {
    header("Location: ../../pages/admin-rentals.php?error=Yêu cầu không hợp lệ");
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE rentals SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: ../../pages/admin-rentals.php?success=Cập nhật trạng thái thuê thành công");
    exit;
} catch (Exception $e) {
    header("Location: ../../pages/admin-rentals.php?error=" . urlencode("Lỗi: " . $e->getMessage()));
    exit;
}



