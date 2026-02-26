<?php
session_start();
include "../db_conn.php";
include "func-user.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$keyword = trim($_GET['keyword'] ?? '');

if (empty($keyword) || strlen($keyword) < 2) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập ít nhất 2 ký tự']);
    exit;
}

try {
    $users = search_users($conn, $keyword);
    if ($users != 0) {
        echo json_encode(['success' => true, 'users' => $users]);
    } else {
        echo json_encode(['success' => true, 'users' => []]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}

