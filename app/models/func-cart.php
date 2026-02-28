<?php

function get_cart_items($conn, $user_id) {
    // Lấy thông tin sách từ cart
    $stmt = $conn->prepare("SELECT c.id, c.user_id, c.book_id, c.quantity, c.created_at,
                                   b.title, b.cover, b.price, b.stock, b.discount_percent, b.is_promotion, b.category_id
                            FROM cart c 
                            JOIN books b ON c.book_id = b.id 
                            WHERE c.user_id = ?");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($items) == 0) {
        return 0;
    }
    
    // Tính giá sau discount cho mỗi item
    foreach ($items as &$item) {
        // Kiểm tra promotion cho sách này
        $promo_discount = get_book_promo_discount($conn, $item['book_id']);
        
        // Lấy discount cao nhất giữa promotion và discount gốc của sách
        $book_discount = isset($item['discount_percent']) ? (float)$item['discount_percent'] : 0;
        $final_discount = max($promo_discount, $book_discount);
        
        $item['final_discount'] = $final_discount;
        $original_price = (float)$item['price'];
        $item['original_price'] = $original_price;
        $item['final_price'] = $original_price * (100 - $final_discount) / 100;
        $item['price'] = $item['final_price'];
    }
    unset($item);
    
    return $items;
}

function get_book_promo_discount($conn, $book_id) {
    try {
        $stmt = $conn->prepare("SELECT COALESCE(pb.custom_discount_percent, p.discount_percent, 0) as promo_discount
                                FROM promotion_books pb
                                JOIN promotions p ON pb.promotion_id = p.id
                                WHERE pb.book_id = ? AND p.is_active = 1 
                                  AND p.start_date <= NOW() AND p.end_date >= NOW()
                                ORDER BY COALESCE(pb.custom_discount_percent, p.discount_percent) DESC
                                LIMIT 1");
        $stmt->execute([$book_id]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $promo ? (float)$promo['promo_discount'] : 0;
    } catch (PDOException $e) {
        return 0;
    }
}

function get_cart_count($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function add_to_cart($conn, $user_id, $book_id, $quantity = 1) {
    // Kiểm tra sách có tồn tại và còn hàng không
    $book_stmt = $conn->prepare("SELECT id, stock, title FROM books WHERE id = ?");
    $book_stmt->execute([$book_id]);
    $book = $book_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        return ['success' => false, 'message' => 'Sách không tồn tại'];
    }
    
    if ($book['stock'] <= 0) {
        return ['success' => false, 'message' => 'Sách đã hết hàng'];
    }
    
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$user_id, $book_id]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists) {
        // Kiểm tra số lượng trong giỏ + số lượng thêm không vượt quá stock
        $new_quantity = $exists['quantity'] + $quantity;
        if ($new_quantity > $book['stock']) {
            return ['success' => false, 'message' => 'Số lượng vượt quá tồn kho (còn ' . $book['stock'] . ' cuốn)'];
        }
        
        $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND book_id = ?");
        $result = $stmt->execute([$quantity, $user_id, $book_id]);
        return ['success' => $result, 'message' => 'Đã thêm vào giỏ hàng', 'book_title' => $book['title'], 'new_quantity' => $new_quantity];
    } else {
        if ($quantity > $book['stock']) {
            return ['success' => false, 'message' => 'Số lượng vượt quá tồn kho (còn ' . $book['stock'] . ' cuốn)'];
        }
        
        $stmt = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
        $result = $stmt->execute([$user_id, $book_id, $quantity]);
        return ['success' => $result, 'message' => 'Đã thêm vào giỏ hàng', 'book_title' => $book['title'], 'new_quantity' => $quantity];
    }
}

function remove_from_cart($conn, $user_id, $book_id) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND book_id = ?");
    return $stmt->execute([$user_id, $book_id]);
}

function clear_cart($conn, $user_id) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

function get_cart_total($conn, $user_id) {
    $items = get_cart_items($conn, $user_id);
    
    if ($items == 0) {
        return 0;
    }
    
    $total = 0;
    foreach ($items as $item) {
        $total += (float)$item['final_price'] * (int)$item['quantity'];
    }
    
    return $total;
}
