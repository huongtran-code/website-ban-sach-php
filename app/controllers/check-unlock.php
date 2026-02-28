<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-user.php";
include __DIR__ . "/../models/func-rental.php";
include __DIR__ . "/../models/func-transaction.php";

if (!isset($_SESSION['locked_user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

$user_id = $_SESSION['locked_user_id'];
$lock_status = check_overdue_lock_status($conn, $user_id);

if ($lock_status['status'] == 'ok') {
    // Already unlocked (maybe manual admin intervention or other magic)
    unban_user($conn, $user_id);
    
    // Log user in
    $user = get_user_by_id($conn, $user_id);
    $_SESSION['customer_id'] = $user['id'];
    $_SESSION['customer_name'] = $user['full_name'];
    $_SESSION['customer_email'] = $user['email'];
    
    unset($_SESSION['locked_user_id']);
    unset($_SESSION['locked_reason']);
    unset($_SESSION['locked_amount']);
    unset($_SESSION['locked_penalty']);
    unset($_SESSION['locked_balance']);
    
    header("Location: ../../pages/index.php?success=" . urlencode("Tài khoản đã được mở khóa!"));
} elseif ($lock_status['status'] == 'locked' || $lock_status['status'] == 'warning') {
    // User is locked or active warning (which means overdue books exist)
    // We need to check if they have enough balance to pay for ALL overdue books + penalties
    // The previous check_overdue_lock_status returned 'locked' because balance < penalty
    // If they have topped up, balance should now be >= penalty
    
    // Re-calculate penalty (to be safe and exact)
    $active_rentals_sql = "SELECT r.*, b.title, b.rental_price FROM rentals r JOIN books b ON r.book_id = b.id WHERE r.user_id = ? AND r.status = 'active'";
    $stmt = $conn->prepare($active_rentals_sql);
    $stmt->execute([$user_id]);
    $active_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $overdue_items = [];
    $total_penalty = 0;
    
    foreach ($active_rentals as $item) {
        if (strtotime($item['end_date']) < time()) {
            $days_late = ceil((time() - strtotime($item['end_date'])) / 86400);
            $days_late = max(1, $days_late);
            
            $base_price = $item['rental_price'] > 0 ? (float)$item['rental_price'] : 10000;
            $daily_price = $base_price / 7;
            $penalty = round($days_late * $daily_price, -3);
            
            $total_penalty += $penalty;
            $overdue_items[] = [
                'rental_id' => $item['id'],
                'book_title' => $item['title'],
                'penalty' => $penalty
            ];
        }
    }

    $user = get_user_by_id($conn, $user_id);
    $current_balance = (float)$user['balance'];
    
    if ($current_balance >= $total_penalty) {
        // Have enough money!
        try {
            $conn->beginTransaction();
            
            // 1. Deduct money
            $new_balance = $current_balance - $total_penalty;
            $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $user_id]);
            
            // 2. Add transaction record
            add_transaction($conn, $user_id, 'rental_penalty', $total_penalty, "Thanh toán phí phạt quá hạn để mở khóa tài khoản");
            
            // 3. Mark items as returned (or extended? usually returned/paid off)
            // For simplicity and strictness: mark as returned. User has to rent again if they want to read.
            $stmt_update_rental = $conn->prepare("UPDATE rentals SET status = 'returned', returned_at = NOW() WHERE id = ?");
            
            foreach ($overdue_items as $item) {
                $stmt_update_rental->execute([$item['rental_id']]);
            }
            
            // 4. Unban user
            unban_user($conn, $user_id);
            
            $conn->commit();
            
            // Log user in
            $_SESSION['customer_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['full_name'];
            $_SESSION['customer_email'] = $user['email'];
            
            unset($_SESSION['locked_user_id']);
            unset($_SESSION['locked_reason']);
            unset($_SESSION['locked_amount']);
            unset($_SESSION['locked_penalty']);
            unset($_SESSION['locked_balance']);
            
            header("Location: ../../pages/index.php?success=" . urlencode("Đã thanh toán " . number_format($total_penalty) . "đ. Tài khoản đã được mở khóa!"));
        } catch (Exception $e) {
            $conn->rollBack();
             header("Location: ../../pages/locked.php?error=" . urlencode("Lỗi xử lý: " . $e->getMessage()));
        }
    } else {
        // Still not enough
        $shortfall = $total_penalty - $current_balance;
        $_SESSION['locked_amount'] = $shortfall;
        $_SESSION['locked_penalty'] = $total_penalty;
        $_SESSION['locked_balance'] = $current_balance;
        
         header("Location: ../../pages/locked.php?error=" . urlencode("Số dư không đủ để thanh toán phí phạt (" . number_format($total_penalty) . "đ). Vui lòng nạp thêm " . number_format($shortfall) . "đ."));
    }
}
