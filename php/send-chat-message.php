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
$message = trim($_POST['message'] ?? '');
$message_type = 'text';
$image_url = null;

// Xử lý upload ảnh
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/chat/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (jpg, png, gif, webp)']);
        exit;
    }
    
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Kích thước ảnh không được vượt quá 5MB']);
        exit;
    }
    
    $file_name = 'chat_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        $image_url = 'uploads/chat/' . $file_name;
        $message_type = 'image';
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể upload ảnh']);
        exit;
    }
}

if (empty($message) && empty($image_url)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tin nhắn hoặc chọn ảnh']);
    exit;
}

try {
    if (add_chat_message($conn, $user_id, null, $message, 0, $message_type, $image_url)) {
        echo json_encode(['success' => true, 'message' => 'Gửi tin nhắn thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể gửi tin nhắn']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}




