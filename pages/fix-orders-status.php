<?php
// Script để cập nhật trạng thái các đơn hàng PDF cũ thành 'completed'
require_once __DIR__ . "/../config/bootstrap.php";

echo "<h2>Đang cập nhật trạng thái đơn hàng PDF...</h2>";

try {
    // Lấy tất cả đơn hàng có status = 'pending'
    $stmt = $conn->prepare("SELECT id FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    $skipped = 0;
    
    foreach ($pending_orders as $order) {
        $order_id = $order['id'];
        
        // Kiểm tra xem đơn hàng này có sách bản cứng không
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ? AND book_type = 'hardcopy'");
        $stmt->execute([$order_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Nếu không có bản cứng (chỉ có PDF), cập nhật thành 'completed'
        if ($result['count'] == 0) {
            $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
            $stmt->execute([$order_id]);
            $updated++;
            echo "<p style='color: green;'>✓ Đã cập nhật đơn hàng #$order_id thành 'Hoàn thành'</p>";
        } else {
            $skipped++;
            echo "<p style='color: orange;'>⚠ Bỏ qua đơn hàng #$order_id (có bản cứng)</p>";
        }
    }
    
    echo "<br><h3 style='color: green;'>✓ Hoàn thành!</h3>";
    echo "<p>Cập nhật: $updated đơn hàng</p>";
    echo "<p>Bỏ qua: $skipped đơn hàng (có bản cứng)</p>";
    echo "<p><a href='admin-orders.php'>Về trang quản lý đơn hàng</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
?>





