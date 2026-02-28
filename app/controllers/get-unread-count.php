<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-chat.php";

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['locked_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

try {
    $user_id = $_SESSION['customer_id'] ?? $_SESSION['locked_user_id'];
    $unread_count = get_unread_messages_count($conn, $user_id);
    echo json_encode(['success' => true, 'unread_count' => $unread_count]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'unread_count' => 0]);
}




