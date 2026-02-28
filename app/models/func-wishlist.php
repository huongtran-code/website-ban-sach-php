<?php

function get_wishlist_items($conn, $user_id) {
    $stmt = $conn->prepare("SELECT w.*, b.title, b.cover, b.price, b.stock, b.author_id 
                            FROM wishlist w 
                            JOIN books b ON w.book_id = b.id 
                            WHERE w.user_id = ?");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($items) > 0 ? $items : 0;
}

function get_wishlist_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function add_to_wishlist($conn, $user_id, $book_id) {
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$user_id, $book_id]);
    if ($stmt->fetch()) {
        return true;
    }
    
    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)");
    return $stmt->execute([$user_id, $book_id]);
}

function remove_from_wishlist($conn, $user_id, $book_id) {
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
    return $stmt->execute([$user_id, $book_id]);
}

function is_in_wishlist($conn, $user_id, $book_id) {
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$user_id, $book_id]);
    return $stmt->fetch() ? true : false;
}
