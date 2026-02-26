<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
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
        header("Location: ../order-detail.php?id=$order_id&error=Không tìm thấy đơn hàng");
        exit;
    }
    
    // Chỉ cho phép hoàn trả khi đơn hàng đã được giao hoặc hoàn thành
    if ($order['status'] != 'shipped' && $order['status'] != 'completed') {
        header("Location: ../order-detail.php?id=$order_id&error=Chỉ có thể hoàn trả khi đơn hàng đã được giao");
        exit;
    }
    
    // Kiểm tra xem đơn hàng có bản cứng không (chỉ cho phép hoàn trả bản cứng)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ? AND book_type = 'hardcopy'");
    $stmt->execute([$order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        header("Location: ../order-detail.php?id=$order_id&error=Chỉ có thể hoàn trả sách bản cứng");
        exit;
    }
    
    // Kiểm tra thời gian hoàn trả dựa trên số ngày cho phép của sách (return_days)
    // Lấy số ngày hoàn trả nhỏ nhất trong các sách bản cứng của đơn + số ngày đã trôi qua từ khi đặt
    $stmt = $conn->prepare("
        SELECT 
            MIN(COALESCE(b.return_days, 7)) AS min_return_days,
            TIMESTAMPDIFF(DAY, o.created_at, NOW()) AS days_since_order
        FROM order_items oi
        JOIN books b ON oi.book_id = b.id
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.order_id = ? AND oi.book_type = 'hardcopy'
    ");
    $stmt->execute([$order_id]);
    $return_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($return_info) {
        $min_return_days = (int)($return_info['min_return_days'] ?? 7);
        $days_since_order = (int)($return_info['days_since_order'] ?? 0);

        if ($days_since_order > $min_return_days) {
            $message = "Đơn hàng đã quá thời gian cho phép hoàn trả ({$min_return_days} ngày) kể từ ngày mua. Hiện đã là {$days_since_order} ngày.";
            header("Location: ../order-detail.php?id=$order_id&error=" . urlencode($message));
            exit;
        }
    }
    
    // Cập nhật trạng thái thành 'return_requested'
    $stmt = $conn->prepare("UPDATE orders SET status = 'return_requested' WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$order_id, $user_id])) {
        $refund_amount = $order['total_amount'] * 0.85;
        $fee_amount = $order['total_amount'] * 0.15;
        $message = "Yêu cầu hoàn trả đã được gửi. Khi được xác nhận, bạn sẽ nhận lại " . number_format($refund_amount, 0, ',', '.') . "đ (mất 15% phí hoàn trả: " . number_format($fee_amount, 0, ',', '.') . "đ).";
        header("Location: ../order-detail.php?id=$order_id&success=" . urlencode($message));
    } else {
        header("Location: ../order-detail.php?id=$order_id&error=Có lỗi xảy ra");
    }
    exit;
} else {
    header("Location: ../my-orders.php?error=Yêu cầu không hợp lệ");
    exit;
}

