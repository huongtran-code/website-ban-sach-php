<?php

// Mapping loại giao dịch → tên hiển thị, icon, màu badge
function get_transaction_type_info($type) {
    $types = [
        'deposit'        => ['label' => 'Nạp tiền',       'icon' => 'fa-wallet',              'color' => 'primary',  'direction' => '+'],
        'purchase'       => ['label' => 'Mua sách',       'icon' => 'fa-shopping-bag',         'color' => 'warning',  'direction' => '-'],
        'rental'         => ['label' => 'Thuê sách',      'icon' => 'fa-book-reader',          'color' => 'info',     'direction' => '-'],
        'rental_extend'  => ['label' => 'Gia hạn',        'icon' => 'fa-clock-rotate-left',    'color' => 'secondary','direction' => '-'],
        'rental_penalty' => ['label' => 'Phạt trễ',       'icon' => 'fa-gavel',                'color' => 'danger',   'direction' => '-'],
        'refund'         => ['label' => 'Hoàn tiền',      'icon' => 'fa-rotate-left',          'color' => 'success',  'direction' => '+'],
        'revenue'        => ['label' => 'DT Khác',        'icon' => 'fa-arrow-trend-up',       'color' => 'dark',     'direction' => '+'],
        'revenue_order'  => ['label' => 'DT Đơn hàng',    'icon' => 'fa-cash-register',        'color' => 'success',  'direction' => '+'],
        'revenue_rental' => ['label' => 'DT Thuê sách',   'icon' => 'fa-hand-holding-dollar',  'color' => 'success',  'direction' => '+'],
        'expense'        => ['label' => 'Chi phí',        'icon' => 'fa-arrow-trend-down',     'color' => 'danger',   'direction' => '-'],
    ];
    return $types[$type] ?? ['label' => ucfirst($type), 'icon' => 'fa-circle', 'color' => 'dark', 'direction' => ''];
}

// Lấy tất cả loại giao dịch có trong hệ thống
function get_all_transaction_types() {
    return ['deposit', 'purchase', 'rental', 'rental_extend', 'rental_penalty', 'refund', 'revenue', 'revenue_order', 'revenue_rental', 'expense'];
}

// Kiểm tra type có phải nhóm doanh thu (revenue) không
function is_revenue_type($type) {
    return in_array($type, ['revenue', 'revenue_order', 'revenue_rental']);
}

// Kiểm tra type có phải nhóm chi phí (expense) không
function is_expense_type($type) {
    return in_array($type, ['expense']);
}

function get_all_transactions($conn, $limit = 50, $type_filter = '') {
    $limit = (int)$limit;
    $limit = max(1, min(1000, $limit));
    
    $where = '';
    $params = [];
    
    if ($type_filter !== '' && $type_filter !== 'all') {
        // Nhóm lọc đặc biệt
        if ($type_filter === 'group_revenue') {
            $where = "WHERE t.type IN ('revenue', 'revenue_order', 'revenue_rental')";
        } elseif ($type_filter === 'group_rental') {
            $where = "WHERE t.type IN ('rental', 'rental_extend', 'rental_penalty')";
        } else {
            $where = "WHERE t.type = ?";
            $params[] = $type_filter;
        }
    }
    
    $stmt = $conn->prepare("SELECT t.*, u.full_name as user_name FROM transactions t 
                            LEFT JOIN users u ON t.user_id = u.id 
                            $where
                            ORDER BY t.created_at DESC LIMIT " . $limit);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($transactions) > 0 ? $transactions : 0;
}

function get_total_revenue($conn) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type IN ('revenue', 'revenue_order', 'revenue_rental')");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function get_total_expense($conn) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'expense'");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function get_total_deposits($conn) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'deposit'");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

// Thống kê chi tiết theo từng loại
function get_transaction_summary($conn) {
    $stmt = $conn->prepare("SELECT type, COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
                            FROM transactions GROUP BY type ORDER BY total DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function add_transaction($conn, $user_id, $type, $amount, $description) {
    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $type, $amount, $description]);
}
