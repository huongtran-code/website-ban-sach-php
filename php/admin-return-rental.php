<?php
session_start();
include "../db_conn.php";
include "func-rental.php";
include "func-user.php";
include "func-transaction.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../adminlogin.php");
    exit;
}

$rental_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$rental_id) {
    header("Location: ../admin-rentals.php?error=ID không hợp lệ");
    exit;
}

$rental = get_rental_by_id($conn, $rental_id);
if (!$rental) {
    header("Location: ../admin-rentals.php?error=Không tìm thấy thông tin thuê sách");
    exit;
}

// Tính phí phạt quá hạn (nếu có)
$overdue_info = calculate_overdue_penalty($conn, $rental_id);
$overdue_penalty = 0;

if ($overdue_info && $overdue_info['is_overdue']) {
    $overdue_penalty = $overdue_info['penalty'];
}

try {
    $conn->beginTransaction();
    
    // Nếu có phí phạt → trừ tiền user
    if ($overdue_penalty > 0) {
        $user = get_user_by_id($conn, $rental['user_id']);
        
        if ((float)$user['balance'] >= $overdue_penalty) {
            // Trừ tiền
            $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$overdue_penalty, $rental['user_id']]);
            
            // Ghi transaction
            add_transaction($conn, $rental['user_id'], 'rental_penalty', $overdue_penalty, 
                "Phí phạt quá hạn (admin xử lý): " . $rental['title'] . " (" . $overdue_info['days_late'] . " ngày trễ)");
            
            add_transaction($conn, null, 'revenue_rental', $overdue_penalty, 
                "Thu phí phạt quá hạn (admin): " . $rental['title']);
        } else {
            // Không đủ tiền - vẫn trả sách nhưng ghi nợ
            add_transaction($conn, $rental['user_id'], 'rental_penalty', $overdue_penalty, 
                "Ghi nợ phí phạt quá hạn (admin xử lý): " . $rental['title'] . " (" . $overdue_info['days_late'] . " ngày trễ) - User không đủ số dư");
        }
    }
    
    // Trả sách
    return_rental($conn, $rental_id);
    
    $conn->commit();
    
    $msg = "Đã xác nhận trả sách từ " . $rental['full_name'];
    if ($overdue_penalty > 0) {
        $msg .= " (phí phạt: " . number_format($overdue_penalty) . "đ)";
    }
    header("Location: ../admin-rentals.php?success=" . urlencode($msg));
} catch (Exception $e) {
    $conn->rollBack();
    header("Location: ../admin-rentals.php?error=" . urlencode("Lỗi: " . $e->getMessage()));
}
