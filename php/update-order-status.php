<?php
session_start();
include "../db_conn.php";
include "func-user.php";
include "func-transaction.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = trim($_POST['status']);

    // Validate status
    $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'return_requested', 'returned', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        header("Location: ../admin-order-detail.php?id=$order_id&error=Trạng thái không hợp lệ");
        exit;
    }

    try {
        $conn->beginTransaction();

        // Lấy thông tin đơn hàng
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Không tìm thấy đơn hàng");
        }

        $old_status = $order['status'];
        $user_id = $order['user_id'];

        // Nếu cập nhật thành 'cancelled', xử lý refund toàn bộ
        if ($status == 'cancelled' && $old_status != 'cancelled') {
            // Chỉ refund nếu đơn hàng đã được thanh toán (status khác pending)
            if ($old_status != 'pending') {
                // Refund 100% giá trị đơn hàng
                $refund_amount = $order['total_amount'];

                // Cộng tiền refund vào balance của user
                update_user_balance($conn, $user_id, $refund_amount);

                // Tạo transaction refund cho user
                add_transaction($conn, $user_id, 'refund', $refund_amount, "Hoàn tiền đơn hàng #$order_id (Đã hủy)");

                // Tạo transaction expense để ghi nhận việc hủy đơn
                add_transaction($conn, null, 'expense', $refund_amount, "Hủy đơn hàng #$order_id - Hoàn tiền cho khách hàng");
            }

            // Cộng lại stock cho các sách bản cứng
            $stmt = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = ? AND book_type = 'hardcopy'");
            $stmt->execute([$order_id]);
            $return_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($return_items as $item) {
                $stmt = $conn->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['book_id']]);
            }
        }

        // Nếu cập nhật thành 'returned', xử lý refund
        if ($status == 'returned' && $old_status != 'returned') {
            // Tính số tiền refund (85% giá trị đơn hàng, mất 15% phí)
            $refund_amount = $order['total_amount'] * 0.85;
            $fee_amount = $order['total_amount'] * 0.15;

            // Cộng tiền refund vào balance của user
            update_user_balance($conn, $user_id, $refund_amount);

            // Tạo transaction refund cho user
            add_transaction($conn, $user_id, 'refund', $refund_amount, "Hoàn trả đơn hàng #$order_id (85%)");

            // Tạo transaction expense cho phí hoàn trả (15%)
            add_transaction($conn, null, 'expense', $fee_amount, "Phí hoàn trả đơn hàng #$order_id (15%)");

            // Cộng lại stock cho các sách bản cứng
            $stmt = $conn->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = ? AND book_type = 'hardcopy'");
            $stmt->execute([$order_id]);
            $return_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($return_items as $item) {
                $stmt = $conn->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['book_id']]);
            }
        }

        // Cập nhật trạng thái đơn hàng
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        
        // Nếu đơn hàng chuyển sang 'completed', cập nhật total_spent và membership_level
        if ($status == 'completed' && $old_status != 'completed') {
            // Nếu đơn hàng là COD (pending_cod), tạo transaction revenue khi chuyển sang completed
            if ($old_status == 'pending_cod' && $order['total_amount'] > 0) {
                // Kiểm tra xem đã có transaction revenue cho đơn hàng này chưa
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions 
                                       WHERE description LIKE ? AND type = 'revenue'");
                $stmt->execute(["Đơn hàng #$order_id%"]);
                $existing_transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Chỉ tạo transaction nếu chưa có
                if ($existing_transaction['count'] == 0) {
                    // Tính phí ship từ order_items (nếu có)
                    $stmt = $conn->prepare("SELECT SUM(price * quantity) as items_total FROM order_items WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                    $items_result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $items_total = $items_result['items_total'] ?? 0;
                    $shipping_fee = $order['total_amount'] - $items_total;
                    
                    $description = "Đơn hàng #$order_id";
                    if ($shipping_fee > 0) {
                        $description .= " (bao gồm phí ship " . number_format($shipping_fee, 0, ',', '.') . "đ)";
                    }
                    $description .= " (COD - Đã thanh toán)";
                    
                    add_transaction($conn, null, 'revenue_order', $order['total_amount'], $description);
                }
            }
            
            // Tính giá trị đơn hàng (không tính phí ship)
            $order_value = $order['total_amount'];
            // Nếu có phí ship, trừ đi (vì total_amount bao gồm cả phí ship)
            $stmt = $conn->prepare("SELECT SUM(price * quantity) as items_total FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $items_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $items_total = $items_result['items_total'] ?? 0;
            
            // order_value = items_total (giá trị sách, không tính phí ship)
            if ($items_total > 0) {
                $membership_upgrade = update_user_membership($conn, $user_id, $items_total);
                
                // Lưu thông báo lên hạng vào session nếu có (để hiển thị cho user sau)
                if ($membership_upgrade && $membership_upgrade['upgraded']) {
                    $new_level_name = get_membership_name($membership_upgrade['new_level']);
                    // Lưu vào session của user (cần lấy user session)
                    // Tạm thời lưu vào database hoặc dùng cách khác
                    // Có thể thêm vào message trong success redirect
                }
            }
        }

        $conn->commit();

        if ($status == 'cancelled' && $old_status != 'cancelled') {
            if ($old_status != 'pending') {
                header("Location: ../admin-order-detail.php?id=$order_id&success=Hủy đơn hàng thành công! Đã hoàn tiền " . number_format($order['total_amount'], 0, ',', '.') . "đ cho khách hàng");
            } else {
                header("Location: ../admin-order-detail.php?id=$order_id&success=Hủy đơn hàng thành công (đơn hàng chưa thanh toán)");
            }
        } elseif ($status == 'returned') {
            header("Location: ../admin-order-detail.php?id=$order_id&success=Hoàn trả thành công! Đã refund " . number_format($order['total_amount'] * 0.85, 0, ',', '.') . "đ cho khách hàng (mất 15% phí)");
        } else {
            header("Location: ../admin-order-detail.php?id=$order_id&success=Cập nhật trạng thái thành công");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: ../admin-order-detail.php?id=$order_id&error=Có lỗi xảy ra: " . $e->getMessage());
    }
    exit;
} else {
    header("Location: ../admin-orders.php?error=Yêu cầu không hợp lệ");
    exit;
}

