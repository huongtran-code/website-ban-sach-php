<?php

function get_all_categories($conn) {
    // GROUP BY name để chỉ lấy 1 danh mục cho mỗi tên (lấy ID nhỏ nhất)
    $stmt = $conn->prepare("SELECT MIN(id) as id, name 
                           FROM categories 
                           GROUP BY name 
                           ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nếu cần đầy đủ thông tin, lấy lại từ ID
    if (count($categories) > 0) {
        $ids = array_column($categories, 'id');
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id IN ($placeholders) ORDER BY name ASC");
        $stmt->execute($ids);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return count($categories) > 0 ? $categories : 0;
}

function get_category_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
