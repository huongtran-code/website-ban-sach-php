<?php
session_start();
include "../db_conn.php";

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['locked_user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$is_admin = isset($_SESSION['user_id']);
$user_id = $is_admin ? null : ($_SESSION['customer_id'] ?? $_SESSION['locked_user_id']);
$admin_id = $is_admin ? $_SESSION['user_id'] : null;
$is_typing = isset($_POST['is_typing']) && $_POST['is_typing'] == '1';

// Lưu typing status vào session hoặc cache
// Sử dụng file-based cache đơn giản
$cache_file = '../cache/typing_' . ($is_admin ? 'admin_' . $admin_id : 'user_' . $user_id) . '.json';
$cache_dir = dirname($cache_file);
if (!file_exists($cache_dir)) {
    mkdir($cache_dir, 0777, true);
}

$typing_data = [
    'is_typing' => $is_typing,
    'timestamp' => time(),
    'is_admin' => $is_admin,
    'user_id' => $user_id,
    'admin_id' => $admin_id
];

file_put_contents($cache_file, json_encode($typing_data));

echo json_encode(['success' => true]);


