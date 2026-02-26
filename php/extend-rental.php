<?php
session_start();
include "../db_conn.php";
include "func-rental.php";
include "func-user.php";
include "func-transaction.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit;
}

$rental_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$extend_days = isset($_GET['days']) ? max(1, min(30, (int)$_GET['days'])) : 7;

if (!$rental_id) {
    header("Location: ../my-rentals.php?error=ID không hợp lệ");
    exit;
}

// Kiểm tra rental thuộc về user
$rental = get_rental_by_id($conn, $rental_id);
if (!$rental || $rental['user_id'] != $_SESSION['customer_id']) {
    header("Location: ../my-rentals.php?error=Không tìm thấy thông tin thuê sách");
    exit;
}

if ($rental['status'] != 'active') {
    header("Location: ../my-rentals.php?error=Chỉ có thể gia hạn sách đang thuê");
    exit;
}

// Tính phí gia hạn
$fee_info = calculate_extension_fee($conn, $rental_id, $extend_days);
if (!$fee_info) {
    header("Location: ../my-rentals.php?error=Không thể tính phí gia hạn");
    exit;
}

$extension_fee = $fee_info['fee'];

// Nếu quá hạn, cộng thêm phí phạt quá hạn
$overdue_info = calculate_overdue_penalty($conn, $rental_id);
$overdue_penalty = 0;
$total_charge = $extension_fee;

if ($overdue_info && $overdue_info['is_overdue']) {
    $overdue_penalty = $overdue_info['penalty'];
    $total_charge = $extension_fee + $overdue_penalty;
}

// Kiểm tra số dư
$user = get_user_by_id($conn, $_SESSION['customer_id']);
if ((float)$user['balance'] < $total_charge) {
    $shortage = $total_charge - (float)$user['balance'];
    $msg = "Số dư không đủ để gia hạn. ";
    $msg .= "Phí gia hạn: " . number_format($extension_fee) . "đ";
    if ($overdue_penalty > 0) {
        $msg .= " + Phí phạt quá hạn: " . number_format($overdue_penalty) . "đ";
    }
    $msg .= ". Cần nạp thêm: " . number_format($shortage) . "đ.";
    header("Location: ../my-rentals.php?error=" . urlencode($msg));
    exit;
}

try {
    $conn->beginTransaction();
    
    // 1. Trừ tiền
    $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$total_charge, $_SESSION['customer_id']]);
    
    // 2. Ghi transaction phí gia hạn
    add_transaction($conn, $_SESSION['customer_id'], 'rental_extend', $extension_fee, 
        "Gia hạn thuê sách: " . $rental['title'] . " (+" . $extend_days . " ngày)");
    
    // 3. Nếu có phí phạt quá hạn, ghi riêng
    if ($overdue_penalty > 0) {
        add_transaction($conn, $_SESSION['customer_id'], 'rental_penalty', $overdue_penalty, 
            "Phí phạt quá hạn: " . $rental['title'] . " (" . $overdue_info['days_late'] . " ngày trễ)");
    }
    
    // 4. Ghi doanh thu
    add_transaction($conn, null, 'revenue_rental', $total_charge, 
        "Phí gia hạn" . ($overdue_penalty > 0 ? " + phạt quá hạn" : "") . ": " . $rental['title']);
    
    // 5. Gia hạn
    extend_rental($conn, $rental_id, $extend_days);
    
    $conn->commit();
    
    $msg = "Đã gia hạn thêm " . $extend_days . " ngày (phí: " . number_format($total_charge) . "đ";
    if ($overdue_penalty > 0) {
        $msg .= ", gồm " . number_format($overdue_penalty) . "đ phạt quá hạn";
    }
    $msg .= ")";
    header("Location: ../my-rentals.php?success=" . urlencode($msg));
} catch (Exception $e) {
    $conn->rollBack();
    header("Location: ../my-rentals.php?error=" . urlencode("Lỗi: " . $e->getMessage()));
}
