<?php

function get_chat_messages($conn, $user_id = null, $admin_id = null, $limit = 50) {
    $limit = (int)$limit;
    $limit = max(1, min(200, $limit));
    
    if ($user_id !== null) {
        // Lấy tin nhắn của user cụ thể
        $stmt = $conn->prepare("SELECT cm.*, u.full_name as user_name, a.full_name as admin_name
                               FROM chat_messages cm
                               LEFT JOIN users u ON cm.user_id = u.id
                               LEFT JOIN admin a ON cm.admin_id = a.id
                               WHERE cm.user_id = ?
                               ORDER BY cm.created_at ASC
                               LIMIT " . $limit);
        $stmt->execute([$user_id]);
    } elseif ($admin_id !== null) {
        // Lấy tất cả tin nhắn cho admin
        $stmt = $conn->prepare("SELECT cm.*, u.full_name as user_name, a.full_name as admin_name
                               FROM chat_messages cm
                               LEFT JOIN users u ON cm.user_id = u.id
                               LEFT JOIN admin a ON cm.admin_id = a.id
                               ORDER BY cm.created_at DESC
                               LIMIT " . $limit);
        $stmt->execute();
    } else {
        // Lấy tất cả tin nhắn
        $stmt = $conn->prepare("SELECT cm.*, u.full_name as user_name, a.full_name as admin_name
                               FROM chat_messages cm
                               LEFT JOIN users u ON cm.user_id = u.id
                               LEFT JOIN admin a ON cm.admin_id = a.id
                               ORDER BY cm.created_at DESC
                               LIMIT " . $limit);
        $stmt->execute();
    }
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($messages) > 0 ? $messages : 0;
}

function get_unread_messages_count($conn, $user_id = null, $admin_id = null) {
    if ($user_id !== null) {
        // Đếm tin nhắn chưa đọc của user (tin nhắn từ admin)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM chat_messages 
                               WHERE user_id = ? AND is_admin = 1 AND is_read = 0");
        $stmt->execute([$user_id]);
    } elseif ($admin_id !== null) {
        // Đếm tin nhắn chưa đọc của admin (tin nhắn từ user)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM chat_messages 
                               WHERE is_admin = 0 AND is_read = 0");
        $stmt->execute();
    } else {
        return 0;
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$result['count'];
}

function get_active_chats($conn) {
    // Lấy danh sách các user đã có tin nhắn (để admin xem)
    $stmt = $conn->prepare("SELECT DISTINCT cm.user_id, u.full_name, u.email, 
                           (SELECT COUNT(*) FROM chat_messages WHERE user_id = cm.user_id AND is_admin = 0 AND is_read = 0) as unread_count,
                           (SELECT MAX(created_at) FROM chat_messages WHERE user_id = cm.user_id) as last_message_time
                           FROM chat_messages cm
                           JOIN users u ON cm.user_id = u.id
                           WHERE cm.user_id IS NOT NULL
                           ORDER BY last_message_time DESC");
    $stmt->execute();
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($chats) > 0 ? $chats : 0;
}

function add_chat_message($conn, $user_id, $admin_id, $message, $is_admin = 0, $message_type = 'text', $image_url = null) {
    $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, admin_id, message, is_admin, message_type, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $admin_id, $message, $is_admin, $message_type, $image_url]);
}

function mark_messages_as_read($conn, $user_id = null, $admin_id = null, $specific_user_id = null) {
    try {
        if ($user_id !== null) {
            // Đánh dấu tin nhắn từ admin là đã đọc (cho user)
            $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND is_admin = 1 AND is_read = 0");
            return $stmt->execute([$user_id]);
        } elseif ($admin_id !== null && $specific_user_id !== null) {
            // Đánh dấu tin nhắn từ user cụ thể là đã đọc (cho admin)
            $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND is_admin = 0 AND is_read = 0");
            return $stmt->execute([$specific_user_id]);
        } elseif ($admin_id !== null) {
            // Đánh dấu tất cả tin nhắn từ user là đã đọc (cho admin)
            $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE is_admin = 0 AND is_read = 0");
            return $stmt->execute();
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

