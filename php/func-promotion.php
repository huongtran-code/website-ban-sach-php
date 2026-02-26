<?php

function get_all_promotions($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM promotions ORDER BY created_at DESC");
        $stmt->execute();
        $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($promotions) > 0 ? $promotions : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function get_active_promotions($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM promotions WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW() ORDER BY created_at DESC");
        $stmt->execute();
        $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($promotions) > 0 ? $promotions : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function get_promotion_by_id($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function add_promotion($conn, $name, $description, $discount_percent, $start_date, $end_date, $banner_image = null) {
    $stmt = $conn->prepare("INSERT INTO promotions (name, description, discount_percent, start_date, end_date, banner_image) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $description, $discount_percent, $start_date, $end_date, $banner_image]);
}

function update_promotion($conn, $id, $name, $description, $discount_percent, $start_date, $end_date, $banner_image = null) {
    if ($banner_image) {
        $stmt = $conn->prepare("UPDATE promotions SET name = ?, description = ?, discount_percent = ?, start_date = ?, end_date = ?, banner_image = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $discount_percent, $start_date, $end_date, $banner_image, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE promotions SET name = ?, description = ?, discount_percent = ?, start_date = ?, end_date = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $discount_percent, $start_date, $end_date, $id]);
    }
}

function delete_promotion($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
    return $stmt->execute([$id]);
}

function toggle_promotion_status($conn, $id, $is_active) {
    $stmt = $conn->prepare("UPDATE promotions SET is_active = ? WHERE id = ?");
    return $stmt->execute([$is_active, $id]);
}

function get_books_in_promotion($conn, $promotion_id) {
    try {
        $stmt = $conn->prepare("SELECT pb.*, b.id as book_id, b.title, b.cover, b.price, b.stock, b.author_id, b.category_id, b.discount_percent as book_discount 
                                FROM promotion_books pb 
                                JOIN books b ON pb.book_id = b.id 
                                WHERE pb.promotion_id = ?
                                GROUP BY b.id");
        $stmt->execute([$promotion_id]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($books) > 0 ? $books : 0;
    } catch (PDOException $e) {
        error_log("get_books_in_promotion error: " . $e->getMessage());
        return 0;
    }
}

function add_book_to_promotion($conn, $promotion_id, $book_id, $custom_discount = null) {
    try {
        // Kiểm tra xem đã có chưa
        $check = $conn->prepare("SELECT id FROM promotion_books WHERE promotion_id = ? AND book_id = ?");
        $check->execute([$promotion_id, $book_id]);
        
        if ($check->fetch()) {
            // Đã có, update
            $stmt = $conn->prepare("UPDATE promotion_books SET custom_discount_percent = ? WHERE promotion_id = ? AND book_id = ?");
            return $stmt->execute([$custom_discount, $promotion_id, $book_id]);
        } else {
            // Chưa có, insert
            $stmt = $conn->prepare("INSERT INTO promotion_books (promotion_id, book_id, custom_discount_percent) VALUES (?, ?, ?)");
            return $stmt->execute([$promotion_id, $book_id, $custom_discount]);
        }
    } catch (PDOException $e) {
        error_log("add_book_to_promotion error: " . $e->getMessage());
        return false;
    }
}

function remove_book_from_promotion($conn, $promotion_id, $book_id) {
    $stmt = $conn->prepare("DELETE FROM promotion_books WHERE promotion_id = ? AND book_id = ?");
    return $stmt->execute([$promotion_id, $book_id]);
}

function get_books_with_active_promotions($conn, $limit = 20) {
    try {
        $limit = (int)$limit;
        $stmt = $conn->query("SELECT b.*, p.id as promotion_id, p.name as promotion_name, p.discount_percent as promo_discount,
                                       COALESCE(pb.custom_discount_percent, p.discount_percent) as final_discount
                                FROM books b
                                JOIN promotion_books pb ON b.id = pb.book_id
                                JOIN promotions p ON pb.promotion_id = p.id
                                WHERE p.is_active = 1 AND p.start_date <= NOW() AND p.end_date >= NOW()
                                ORDER BY p.created_at DESC
                                LIMIT $limit");
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return count($books) > 0 ? $books : 0;
    } catch (PDOException $e) {
        error_log("get_books_with_active_promotions error: " . $e->getMessage());
        return 0;
    }
}
