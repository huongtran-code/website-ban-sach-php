<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['locked_user_id'])) {
    echo json_encode(['success' => false, 'is_typing' => false]);
    exit;
}

$is_admin = isset($_SESSION['user_id']);
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Nếu là admin, lấy typing status của user cụ thể
// Nếu là user, lấy typing status của admin (bất kỳ admin nào)
if ($is_admin && $user_id) {
    // Admin đang xem chat với user cụ thể, lấy typing status của user đó
    $cache_file = __DIR__ . '/../../storage/cache/typing_user_' . $user_id . '.json';
} elseif (!$is_admin) {
    // User đang chat, lấy typing status của admin
    // Tìm file cache typing_admin_* mới nhất (phù hợp nếu có nhiều admin)
    $cache_files = glob(__DIR__ . '/../../storage/cache/typing_admin_*.json');
    if ($cache_files && count($cache_files) > 0) {
        // Lấy file mới nhất theo thời gian chỉnh sửa
        usort($cache_files, function($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });
        $cache_file = $cache_files[0];
    } else {
        // Không có admin nào đang gõ
        echo json_encode(['success' => true, 'is_typing' => false]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'is_typing' => false]);
    exit;
}

$is_typing = false;
if (file_exists($cache_file)) {
    $typing_data = json_decode(file_get_contents($cache_file), true);
    if ($typing_data && isset($typing_data['is_typing'])) {
        // Kiểm tra xem typing status còn hợp lệ không (trong vòng 5 giây)
        $time_diff = time() - ($typing_data['timestamp'] ?? 0);
        if ($time_diff < 5 && $typing_data['is_typing']) {
            $is_typing = true;
        }
    }
}

echo json_encode(['success' => true, 'is_typing' => $is_typing]);

