<?php
session_start();
include "../db_conn.php";
include "func-chat.php";

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['locked_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['customer_id'] ?? $_SESSION['locked_user_id'];
$messages = get_chat_messages($conn, $user_id, null, 100);

if ($messages != 0) {
    echo json_encode(['success' => true, 'messages' => $messages]);
} else {
    echo json_encode(['success' => true, 'messages' => []]);
}




