<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-user.php";
include __DIR__ . "/../models/func-transaction.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['customer_id'];
$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
$method = isset($_POST['method']) ? trim($_POST['method']) : 'Unknown';

// Validate
if ($amount < 10000) {
    echo json_encode(['success' => false, 'message' => 'Số tiền nạp tối thiểu là 10.000đ']);
    exit;
}

if ($amount > 100000000) {
    echo json_encode(['success' => false, 'message' => 'Số tiền nạp tối đa là 100.000.000đ']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Cộng tiền vào tài khoản user
    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$amount, $user_id]);
    
    // Tạo transaction deposit cho user
    $description = "Nạp tiền qua $method (Demo)";
    add_transaction($conn, $user_id, 'deposit', $amount, $description);
    
    $conn->commit();
    
    // Format số tiền
    $amount_formatted = number_format($amount, 0, ',', '.') . 'đ';
    
    echo json_encode([
        'success' => true,
        'message' => 'Nạp tiền thành công',
        'amount' => $amount,
        'amount_formatted' => $amount_formatted,
        'method' => $method
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
