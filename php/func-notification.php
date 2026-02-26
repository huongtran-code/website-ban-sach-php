<?php

/**
 * Lấy các đơn hàng gần đây để hiển thị thông báo
 * Chỉ lấy đơn trong 24 giờ qua
 */
function get_recent_orders($conn, $limit = 10) {
    try {
        // Lấy đơn hàng trong 7 ngày qua (để có dữ liệu hiển thị)
        $stmt = $conn->prepare("SELECT o.id, o.created_at, o.total_amount, u.full_name,
                                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                                       (SELECT b.title FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = o.id LIMIT 1) as first_book_title
                                FROM orders o
                                JOIN users u ON o.user_id = u.id
                                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                ORDER BY o.created_at DESC
                                LIMIT ?");
        $stmt->execute([$limit]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Nếu không có đơn trong 7 ngày, lấy đơn gần nhất
        if (empty($orders)) {
            $stmt = $conn->prepare("SELECT o.id, o.created_at, o.total_amount, u.full_name,
                                           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
                                           (SELECT b.title FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = o.id LIMIT 1) as first_book_title
                                    FROM orders o
                                    JOIN users u ON o.user_id = u.id
                                    ORDER BY o.created_at DESC
                                    LIMIT ?");
            $stmt->execute([$limit]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $orders;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Tạo thông báo từ đơn hàng
 */
function format_order_notification($order) {
    // Ẩn bớt tên để bảo mật
    $name = $order['full_name'];
    if (mb_strlen($name) > 2) {
        $first = mb_substr($name, 0, 1);
        $last = mb_substr($name, -1);
        $hidden = str_repeat('*', min(5, mb_strlen($name) - 2));
        $name = $first . $hidden . $last;
    }
    
    $book_title = $order['first_book_title'] ?? 'sách';
    if (mb_strlen($book_title) > 30) {
        $book_title = mb_substr($book_title, 0, 30) . '...';
    }
    
    $time_ago = time_ago($order['created_at']);
    
    if ($order['item_count'] > 1) {
        return "$name vừa mua \"$book_title\" và " . ($order['item_count'] - 1) . " sản phẩm khác - $time_ago";
    } else {
        return "$name vừa mua \"$book_title\" - $time_ago";
    }
}

/**
 * Chuyển đổi thời gian thành dạng "... trước"
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'vừa xong';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' phút trước';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' giờ trước';
    } else {
        $days = floor($diff / 86400);
        return $days . ' ngày trước';
    }
}
