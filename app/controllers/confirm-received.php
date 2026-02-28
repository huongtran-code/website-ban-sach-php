<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $user_id = $_SESSION['customer_id'];
    
    // Kiểm tra đơn hàng thuộc về user này
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header("Location: ../../pages/order-detail.php?id=$order_id&error=Không tìm thấy đơn hàng");
        exit;
    }
    
    // Chỉ cho phép xác nhận khi đơn hàng đã được giao (shipped)
    if ($order['status'] != 'shipped') {
        header("Location: ../../pages/order-detail.php?id=$order_id&error=Chỉ có thể xác nhận khi đơn hàng đã được giao");
        exit;
    }
    
    // Cập nhật trạng thái thành 'completed'
    $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$order_id, $user_id])) {
        header("Location: ../../pages/order-detail.php?id=$order_id&success=Cảm ơn bạn đã xác nhận nhận hàng!");
    } else {
        header("Location: ../../pages/order-detail.php?id=$order_id&error=Có lỗi xảy ra");
    }
    exit;
} else {
    header("Location: ../../pages/my-orders.php?error=Yêu cầu không hợp lệ");
    exit;
}





