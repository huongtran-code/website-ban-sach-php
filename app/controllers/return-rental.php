<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-rental.php";
include __DIR__ . "/../models/func-user.php";
include __DIR__ . "/../models/func-transaction.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

$rental_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$rental_id) {
    header("Location: ../../pages/my-rentals.php?error=ID không hợp lệ");
    exit;
}

// Kiểm tra rental thuộc về user
$rental = get_rental_by_id($conn, $rental_id);
if (!$rental || $rental['user_id'] != $_SESSION['customer_id']) {
    header("Location: ../../pages/my-rentals.php?error=Không tìm thấy thông tin thuê sách");
    exit;
}

if ($rental['status'] != 'active') {
    header("Location: ../../pages/my-rentals.php?error=Sách này không trong trạng thái đang thuê");
    exit;
}

// Tính phí phạt quá hạn (nếu có)
$overdue_info = calculate_overdue_penalty($conn, $rental_id);
$overdue_penalty = 0;

if ($overdue_info && $overdue_info['is_overdue']) {
    $overdue_penalty = $overdue_info['penalty'];
    
    // Kiểm tra số dư
    $user = get_user_by_id($conn, $_SESSION['customer_id']);
    if ((float)$user['balance'] < $overdue_penalty) {
        $shortage = $overdue_penalty - (float)$user['balance'];
        $msg = "Không thể trả sách vì bạn có phí phạt quá hạn " . number_format($overdue_penalty) . "đ ";
        $msg .= "(" . $overdue_info['days_late'] . " ngày trễ). ";
        $msg .= "Số dư hiện tại: " . number_format($user['balance']) . "đ. ";
        $msg .= "Cần nạp thêm: " . number_format($shortage) . "đ.";
        header("Location: ../../pages/my-rentals.php?error=" . urlencode($msg));
        exit;
    }
}

try {
    $conn->beginTransaction();
    
    // 1. Nếu có phí phạt → trừ tiền
    if ($overdue_penalty > 0) {
        $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$overdue_penalty, $_SESSION['customer_id']]);
        
        // Ghi transaction phí phạt
        add_transaction($conn, $_SESSION['customer_id'], 'rental_penalty', $overdue_penalty, 
            "Phí phạt quá hạn trả sách: " . $rental['title'] . " (" . $overdue_info['days_late'] . " ngày trễ)");
        
        // Ghi doanh thu
        add_transaction($conn, null, 'revenue_rental', $overdue_penalty, 
            "Thu phí phạt quá hạn: " . $rental['title']);
    }
    
    // 2. Trả sách
    return_rental($conn, $rental_id);
    
    $conn->commit();
    
    if ($overdue_penalty > 0) {
        $msg = "Đã trả sách thành công. Phí phạt quá hạn " . $overdue_info['days_late'] . " ngày: " . number_format($overdue_penalty) . "đ đã được trừ.";
    } else {
        $msg = "Đã trả sách thành công!";
    }
    header("Location: ../../pages/my-rentals.php?success=" . urlencode($msg));
} catch (Exception $e) {
    $conn->rollBack();
    header("Location: ../../pages/my-rentals.php?error=" . urlencode("Lỗi: " . $e->getMessage()));
}
