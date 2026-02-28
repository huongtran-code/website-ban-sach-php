<?php

function get_pending_orders($conn, $limit = 10) {
    $limit = (int)$limit;
    $limit = max(1, min(100, $limit));
    
    $stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           WHERE o.status IN ('pending', 'pending_cod') 
                           ORDER BY o.created_at DESC 
                           LIMIT " . $limit);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($orders) > 0 ? $orders : 0;
}

function get_order_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           WHERE o.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_all_orders($conn, $limit = 50) {
    $limit = (int)$limit;
    $limit = max(1, min(1000, $limit));
    
    $stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           ORDER BY o.created_at DESC 
                           LIMIT " . $limit);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($orders) > 0 ? $orders : 0;
}

function get_orders_by_status($conn, $status, $limit = 50) {
    $limit = (int)$limit;
    $limit = max(1, min(1000, $limit));
    
    $stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           WHERE o.status = ? 
                           ORDER BY o.created_at DESC 
                           LIMIT " . $limit);
    $stmt->execute([$status]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($orders) > 0 ? $orders : 0;
}




