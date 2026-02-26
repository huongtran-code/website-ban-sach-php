<?php
session_start();
include "../db_conn.php";
include "func-rental.php";
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

// Admin gia hạn miễn phí (không trừ tiền user)
if (extend_rental($conn, $rental_id, 7)) {
    // Ghi transaction ghi chú
    add_transaction($conn, $rental['user_id'], 'rental_extend', 0, 
        "Admin gia hạn miễn phí: " . $rental['title'] . " (+7 ngày)");
    
    header("Location: ../admin-rentals.php?success=Đã gia hạn miễn phí thêm 7 ngày cho " . urlencode($rental['full_name']));
} else {
    header("Location: ../admin-rentals.php?error=Không thể gia hạn");
}
