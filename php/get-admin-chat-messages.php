<?php
session_start();
include "../db_conn.php";
include "func-chat.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu user_id']);
    exit;
}

$messages = get_chat_messages($conn, $user_id, null, 200);

if ($messages != 0) {
    echo json_encode(['success' => true, 'messages' => $messages]);
} else {
    echo json_encode(['success' => true, 'messages' => []]);
}




