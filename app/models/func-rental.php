<?php
if (defined('_FUNC_RENTAL_PHP_')) return;
define('_FUNC_RENTAL_PHP_', true);

// Lấy danh sách sách đang thuê của user
function get_user_rentals($conn, $user_id, $status = null) {
    $sql = "SELECT r.*, b.title, b.cover, b.rental_price 
            FROM rentals r 
            JOIN books b ON r.book_id = b.id 
            WHERE r.user_id = ?";
    if ($status) {
        $sql .= " AND r.status = ?";
        $stmt = $conn->prepare($sql . " ORDER BY r.created_at DESC");
        $stmt->execute([$user_id, $status]);
    } else {
        $stmt = $conn->prepare($sql . " ORDER BY r.created_at DESC");
        $stmt->execute([$user_id]);
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy tất cả rentals (admin)
function get_all_rentals($conn, $status = null) {
    $sql = "SELECT r.*, b.title, b.cover, b.rental_price, u.full_name, u.email 
            FROM rentals r 
            JOIN books b ON r.book_id = b.id 
            JOIN users u ON r.user_id = u.id";
    if ($status) {
        $sql .= " WHERE r.status = ?";
        $stmt = $conn->prepare($sql . " ORDER BY r.created_at DESC");
        $stmt->execute([$status]);
    } else {
        $stmt = $conn->prepare($sql . " ORDER BY r.created_at DESC");
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Tạo đơn thuê sách
function create_rental($conn, $user_id, $book_id, $days, $price, $auto_extend = 0) {
    $start_date = date('Y-m-d H:i:s');
    $end_date = date('Y-m-d H:i:s', strtotime("+$days days"));
    
    $stmt = $conn->prepare("INSERT INTO rentals (user_id, book_id, price, start_date, end_date, auto_extend, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'active')");
    return $stmt->execute([$user_id, $book_id, $price, $start_date, $end_date, $auto_extend]);
}

// Tính phí gia hạn thuê sách (dựa trên giá thuê/ngày)
function calculate_extension_fee($conn, $rental_id, $days = 7) {
    $stmt = $conn->prepare("SELECT r.*, b.rental_price, b.price as book_price 
                            FROM rentals r JOIN books b ON r.book_id = b.id WHERE r.id = ?");
    $stmt->execute([$rental_id]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rental) return null;
    
    // Tính giá/ngày dựa trên giá thuê gốc và thời gian thuê ban đầu
    $rental_duration_days = max(1, ceil((strtotime($rental['end_date']) - strtotime($rental['start_date'])) / 86400));
    // Nếu đã gia hạn nhiều lần, dùng giá gốc chia cho thời gian ban đầu (7 ngày mặc định)
    $base_days = $rental['extend_count'] > 0 ? 7 : $rental_duration_days;
    
    $base_price = $rental['rental_price'] > 0 ? (float)$rental['rental_price'] : round((float)$rental['book_price'] * 0.3, -3);
    $daily_rate = $base_price / max(1, $base_days);
    
    $extension_fee = round($daily_rate * $days, -3); // Làm tròn 1.000đ
    $extension_fee = max(1000, $extension_fee); // Tối thiểu 1.000đ
    
    return [
        'fee' => $extension_fee,
        'daily_rate' => $daily_rate,
        'days' => $days,
        'rental' => $rental
    ];
}

// Tính phí phạt quá hạn khi trả sách
function calculate_overdue_penalty($conn, $rental_id) {
    $stmt = $conn->prepare("SELECT r.*, b.rental_price, b.price as book_price, b.title
                            FROM rentals r JOIN books b ON r.book_id = b.id WHERE r.id = ?");
    $stmt->execute([$rental_id]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rental) return null;
    
    $end_time = strtotime($rental['end_date']);
    $now = time();
    
    // Chưa quá hạn → không phạt
    if ($now <= $end_time) {
        return [
            'penalty' => 0,
            'days_late' => 0,
            'rental' => $rental,
            'is_overdue' => false
        ];
    }
    
    $days_late = ceil(($now - $end_time) / 86400);
    
    // Giá thuê/ngày
    $base_price = $rental['rental_price'] > 0 ? (float)$rental['rental_price'] : round((float)$rental['book_price'] * 0.3, -3);
    $daily_rate = $base_price / 7; // Mặc định 7 ngày
    
    $penalty = round($days_late * $daily_rate, -3);
    $penalty = max(1000, $penalty); // Tối thiểu 1.000đ
    
    // Giới hạn phí phạt không vượt quá giá mua sách
    $max_penalty = (float)$rental['book_price'];
    $penalty = min($penalty, $max_penalty);
    
    return [
        'penalty' => $penalty,
        'days_late' => $days_late,
        'daily_rate' => $daily_rate,
        'max_penalty' => $max_penalty,
        'rental' => $rental,
        'is_overdue' => true
    ];
}

// Gia hạn thuê sách
function extend_rental($conn, $rental_id, $days = 7) {
    $stmt = $conn->prepare("SELECT * FROM rentals WHERE id = ?");
    $stmt->execute([$rental_id]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rental || $rental['status'] != 'active') {
        return false;
    }
    
    // Nếu sách đã quá hạn, gia hạn từ thời điểm hiện tại thay vì từ end_date cũ
    $base_date = $rental['end_date'];
    if (strtotime($rental['end_date']) < time()) {
        $base_date = date('Y-m-d H:i:s');
    }
    
    $new_end_date = date('Y-m-d H:i:s', strtotime($base_date . " +$days days"));
    $stmt = $conn->prepare("UPDATE rentals SET end_date = ?, extend_count = extend_count + 1 WHERE id = ?");
    return $stmt->execute([$new_end_date, $rental_id]);
}

// Trả sách
function return_rental($conn, $rental_id) {
    $stmt = $conn->prepare("UPDATE rentals SET status = 'returned', returned_at = NOW() WHERE id = ?");
    return $stmt->execute([$rental_id]);
}

// Kiểm tra sách đã quá hạn
function check_overdue_rentals($conn) {
    $stmt = $conn->prepare("SELECT r.*, b.rental_price, u.full_name, u.email 
                            FROM rentals r 
                            JOIN books b ON r.book_id = b.id 
                            JOIN users u ON r.user_id = u.id
                            WHERE r.status = 'active' AND r.end_date < NOW()");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Tự động gia hạn hoặc đánh dấu quá hạn
function process_overdue_rentals($conn) {
    include_once __DIR__ . "/func-settings.php";
    include_once __DIR__ . "/func-user.php";
    include_once __DIR__ . "/func-transaction.php";
    
    $auto_extend = get_setting($conn, 'rental_auto_extend', 1);
    $max_late = get_setting($conn, 'rental_max_late', 3);
    
    $overdue = check_overdue_rentals($conn);
    $results = ['extended' => 0, 'expired' => 0, 'warned' => [], 'insufficient_balance' => 0];
    
    foreach ($overdue as $rental) {
        if ($auto_extend && $rental['auto_extend']) {
            // Tính phí gia hạn
            $fee_info = calculate_extension_fee($conn, $rental['id'], 7);
            $ext_fee = $fee_info ? $fee_info['fee'] : 0;
            
            // Kiểm tra số dư
            $user = get_user_by_id($conn, $rental['user_id']);
            $balance = (float)$user['balance'];
            
            if ($balance >= $ext_fee && $ext_fee > 0) {
                try {
                    $conn->beginTransaction();
                    
                    // Trừ tiền
                    $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                    $stmt->execute([$ext_fee, $rental['user_id']]);
                    
                    // Ghi transaction
                    add_transaction($conn, $rental['user_id'], 'rental_extend', $ext_fee, 
                        "Tự động gia hạn: " . $rental['title'] . " (+7 ngày)");
                    add_transaction($conn, null, 'revenue_rental', $ext_fee, 
                        "Phí tự động gia hạn: " . $rental['title']);
                    
                    // Gia hạn
                    extend_rental($conn, $rental['id'], 7);
                    
                    // Tăng late_count
                    $stmt = $conn->prepare("UPDATE rentals SET late_count = late_count + 1 WHERE id = ?");
                    $stmt->execute([$rental['id']]);
                    
                    $conn->commit();
                    $results['extended']++;
                } catch (Exception $e) {
                    $conn->rollBack();
                }
            } else {
                // Không đủ tiền → đánh dấu hết hạn
                $stmt = $conn->prepare("UPDATE rentals SET status = 'expired', late_count = late_count + 1 WHERE id = ?");
                $stmt->execute([$rental['id']]);
                $results['expired']++;
                $results['insufficient_balance']++;
            }
            
            // Kiểm tra cảnh báo
            if ($rental['late_count'] + 1 >= $max_late) {
                $results['warned'][] = $rental;
            }
        } else {
            // Đánh dấu hết hạn
            $stmt = $conn->prepare("UPDATE rentals SET status = 'expired', late_count = late_count + 1 WHERE id = ?");
            $stmt->execute([$rental['id']]);
            $results['expired']++;
        }
    }
    
    return $results;
}

// Kiểm tra user có đang thuê sách này không
function is_book_rented_by_user($conn, $user_id, $book_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = ? AND book_id = ? AND status = 'active'");
    $stmt->execute([$user_id, $book_id]);
    return $stmt->fetchColumn() > 0;
}

// Đếm số sách đang thuê của user
function count_active_rentals($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Lấy rental theo ID
function get_rental_by_id($conn, $rental_id) {
    $stmt = $conn->prepare("SELECT r.*, b.title, b.cover, b.rental_price, u.full_name, u.email 
                            FROM rentals r 
                            JOIN books b ON r.book_id = b.id 
                            JOIN users u ON r.user_id = u.id
                            WHERE r.id = ?");
    $stmt->execute([$rental_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Kiểm tra và khóa tài khoản nếu quá hạn và không đủ tiền
function check_overdue_lock_status($conn, $user_id) {
    if (!function_exists('get_user_by_id')) include_once __DIR__ . "/func-user.php";
    
    // Lấy tất cả active rentals
    $sql = "SELECT r.*, b.rental_price FROM rentals r JOIN books b ON r.book_id = b.id WHERE r.user_id = ? AND r.status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $active_rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $overdue = [];
    foreach ($active_rentals as $r) {
        if (strtotime($r['end_date']) < time()) {
            $overdue[] = $r;
        }
    }
    
    if (empty($overdue)) return ['status' => 'ok'];
    
    // Lấy balance
    $user = get_user_by_id($conn, $user_id);
    $balance = (float)$user['balance'];
    
    $total_needed = 0;
    foreach ($overdue as $rental) {
        $days_late = ceil((time() - strtotime($rental['end_date'])) / 86400);
        $days_late = max(1, $days_late);
        
        // Giá thuê cơ bản (7 ngày) -> Giá mỗi ngày
        $base_price = $rental['rental_price'] > 0 ? (float)$rental['rental_price'] : 10000;
        $daily_price = $base_price / 7;
        
        $needed = $days_late * $daily_price;
        $total_needed += $needed;
    }
    
    // Làm tròn 1000đ
    $total_needed = round($total_needed, -3);
    
    if ($balance < $total_needed) {
        // Lock account
        $reason = "Tài khoản bị khóa tự động do có " . count($overdue) . " sách quá hạn và không đủ số dư (" . number_format($balance, 0, ',', '.') . "đ < " . number_format($total_needed, 0, ',', '.') . "đ) để thanh toán.";
        ban_user($conn, $user_id, $reason);
        return [
            'status' => 'locked', 
            'reason' => $reason,
            'balance' => $balance,
            'penalty' => $total_needed,
            'shortfall' => $total_needed - $balance
        ];
    }
    
    return ['status' => 'warning', 'message' => "Bạn có " . count($overdue) . " sách quá hạn. Vui lòng thanh toán sớm để tránh bị khóa tài khoản.", 'amount' => $total_needed];
}

