<?php
if (defined('_FUNC_USER_PHP_')) return;
define('_FUNC_USER_PHP_', true);

function get_all_users($conn) {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($users) > 0 ? $users : 0;
}

function get_user_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_user_by_email($conn, $email) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function search_users($conn, $keyword) {
    $keyword = "%$keyword%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY full_name ASC");
    $stmt->execute([$keyword, $keyword]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($users) > 0 ? $users : 0;
}

function update_user_balance($conn, $user_id, $amount) {
    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    return $stmt->execute([$amount, $user_id]);
}

function get_membership_level($total_spent) {
    $total = (float)$total_spent;
    if ($total >= 20000000) {
        return 'diamond'; // Kim cương - 10%
    } elseif ($total >= 10000000) {
        return 'gold'; // Vàng - 8%
    } elseif ($total >= 5000000) {
        return 'silver'; // Bạc - 3%
    }
    return 'normal'; // Thường - 0%
}

function get_membership_discount($membership_level) {
    switch ($membership_level) {
        case 'diamond':
            return 10; // 10%
        case 'gold':
            return 8; // 8%
        case 'silver':
            return 3; // 3%
        default:
            return 0;
    }
}

function get_membership_name($membership_level) {
    switch ($membership_level) {
        case 'diamond':
            return 'Kim cương';
        case 'gold':
            return 'Vàng';
        case 'silver':
            return 'Bạc';
        default:
            return 'Thường';
    }
}

function update_user_membership($conn, $user_id, $order_amount) {
    // Lấy hạng hiện tại
    $stmt = $conn->prepare("SELECT membership_level, total_spent FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return null;
    }
    
    $old_level = $user['membership_level'] ?? 'normal';
    
    // Cập nhật total_spent
    $stmt = $conn->prepare("UPDATE users SET total_spent = total_spent + ? WHERE id = ?");
    $stmt->execute([$order_amount, $user_id]);
    
    // Lấy total_spent mới và cập nhật membership_level
    $stmt = $conn->prepare("SELECT total_spent FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $new_level = get_membership_level($user['total_spent']);
        $stmt = $conn->prepare("UPDATE users SET membership_level = ? WHERE id = ?");
        $stmt->execute([$new_level, $user_id]);
        
        // Trả về thông tin để kiểm tra có lên hạng không
        return [
            'old_level' => $old_level,
            'new_level' => $new_level,
            'upgraded' => $old_level != $new_level
        ];
    }
    return null;
}

function ban_user($conn, $user_id, $reason = null) {
    $stmt = $conn->prepare("UPDATE users SET is_banned = 1, ban_reason = ?, banned_at = NOW() WHERE id = ?");
    return $stmt->execute([$reason, $user_id]);
}

function unban_user($conn, $user_id) {
    $stmt = $conn->prepare("UPDATE users SET is_banned = 0, ban_reason = NULL, banned_at = NULL WHERE id = ?");
    return $stmt->execute([$user_id]);
}

function is_user_banned($conn, $user_id) {
    $stmt = $conn->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && !empty($row['is_banned']);
}
