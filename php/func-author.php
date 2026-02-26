<?php

function get_all_author($conn) {
    // GROUP BY name để chỉ lấy 1 tác giả cho mỗi tên (lấy ID nhỏ nhất)
    $stmt = $conn->prepare("SELECT MIN(id) as id, name 
                           FROM authors 
                           GROUP BY name 
                           ORDER BY name ASC");
    $stmt->execute();
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Nếu cần đầy đủ thông tin, lấy lại từ ID
    if (count($authors) > 0) {
        $ids = array_column($authors, 'id');
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $conn->prepare("SELECT * FROM authors WHERE id IN ($placeholders) ORDER BY name ASC");
        $stmt->execute($ids);
        $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return count($authors) > 0 ? $authors : 0;
}

function get_author_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
