<?php

function get_books_sort_clause($sort, $alias = 'b') {
    switch ($sort) {
        case 'price_asc':
            return "ORDER BY {$alias}.price ASC";
        case 'price_desc':
            return "ORDER BY {$alias}.price DESC";
        case 'views_desc':
            return "ORDER BY {$alias}.view_count DESC";
        case 'rating_desc':
            return "ORDER BY {$alias}.average_rating DESC, {$alias}.review_count DESC";
        case 'sales_desc':
            return "ORDER BY sold_count DESC";
        default:
            return "ORDER BY {$alias}.id DESC";
    }
}

function get_all_books($conn, $sort = null) {
    // Lấy ID của sách không trùng tên (lấy ID nhỏ nhất cho mỗi title)
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books GROUP BY title");
    $stmt->execute();
    $book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($book_ids)) {
        return 0;
    }
    
    // Lấy đầy đủ thông tin sách từ các ID đã chọn
    $sort_clause = get_books_sort_clause($sort, 'b');
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    $sql = "SELECT b.*, 
                   (SELECT COALESCE(SUM(oi.quantity), 0) 
                    FROM order_items oi 
                    WHERE oi.book_id = b.id) AS sold_count
            FROM books b
            WHERE b.id IN ($placeholders)
            {$sort_clause}";
    $stmt = $conn->prepare($sql);
    $stmt->execute($book_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($books) > 0 ? $books : 0;
}

function get_book_by_id($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function search_books($conn, $key, $sort = null) {
    // Lấy ID của sách không trùng tên (lấy ID nhỏ nhất cho mỗi title)
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books WHERE title LIKE ? GROUP BY title");
    $stmt->execute(["%$key%"]);
    $book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($book_ids)) {
        return 0;
    }
    
    // Lấy đầy đủ thông tin sách từ các ID đã chọn
    $sort_clause = get_books_sort_clause($sort, 'b');
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    $sql = "SELECT b.*, 
                   (SELECT COALESCE(SUM(oi.quantity), 0) 
                    FROM order_items oi 
                    WHERE oi.book_id = b.id) AS sold_count
            FROM books b
            WHERE b.id IN ($placeholders)
            {$sort_clause}";
    $stmt = $conn->prepare($sql);
    $stmt->execute($book_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($books) > 0 ? $books : 0;
}

function get_books_by_category($conn, $category_id, $sort = null) {
    // Lấy ID của sách không trùng tên (lấy ID nhỏ nhất cho mỗi title)
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books WHERE category_id = ? GROUP BY title");
    $stmt->execute([$category_id]);
    $book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($book_ids)) {
        return 0;
    }
    
    // Lấy đầy đủ thông tin sách từ các ID đã chọn
    $sort_clause = get_books_sort_clause($sort, 'b');
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    $sql = "SELECT b.*, 
                   (SELECT COALESCE(SUM(oi.quantity), 0) 
                    FROM order_items oi 
                    WHERE oi.book_id = b.id) AS sold_count
            FROM books b
            WHERE b.id IN ($placeholders)
            {$sort_clause}";
    $stmt = $conn->prepare($sql);
    $stmt->execute($book_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($books) > 0 ? $books : 0;
}

function get_books_by_author($conn, $author_id, $sort = null) {
    // Lấy ID của sách không trùng tên (lấy ID nhỏ nhất cho mỗi title)
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books WHERE author_id = ? GROUP BY title");
    $stmt->execute([$author_id]);
    $book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($book_ids)) {
        return 0;
    }
    
    // Lấy đầy đủ thông tin sách từ các ID đã chọn
    $sort_clause = get_books_sort_clause($sort, 'b');
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    $sql = "SELECT b.*, 
                   (SELECT COALESCE(SUM(oi.quantity), 0) 
                    FROM order_items oi 
                    WHERE oi.book_id = b.id) AS sold_count
            FROM books b
            WHERE b.id IN ($placeholders)
            {$sort_clause}";
    $stmt = $conn->prepare($sql);
    $stmt->execute($book_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($books) > 0 ? $books : 0;
}

function get_new_books($conn, $limit = 20) {
    $limit = (int)$limit; // Cast to integer for safety
    $limit = max(1, min(1000, $limit)); // Ensure between 1 and 1000
    
    // Lấy ID của sách không trùng tên (lấy ID nhỏ nhất cho mỗi title)
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books WHERE is_new = 1 GROUP BY title ORDER BY id DESC LIMIT " . $limit);
    $stmt->execute();
    $book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($book_ids)) {
        return 0;
    }
    
    // Lấy đầy đủ thông tin sách từ các ID đã chọn
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM books WHERE id IN ($placeholders) ORDER BY id DESC");
    $stmt->execute($book_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($books) > 0 ? $books : 0;
}

function get_bestseller_books($conn, $limit = 20) {
    $limit = (int)$limit; // Cast to integer for safety
    $limit = max(1, min(1000, $limit)); // Ensure between 1 and 1000
    
    // Lấy ID của sách không trùng tên (lấy ID nhỏ nhất cho mỗi title)
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books GROUP BY title");
    $stmt->execute();
    $book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($book_ids)) {
        return 0;
    }
    
    // Lấy đầy đủ thông tin sách từ các ID đã chọn và sắp xếp theo view_count
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM books WHERE id IN ($placeholders) ORDER BY view_count DESC LIMIT " . $limit);
    $stmt->execute($book_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($books) > 0 ? $books : 0;
}

function get_promotion_books($conn, $limit = 20) {
    $limit = (int)$limit; // Cast to integer for safety
    $limit = max(1, min(1000, $limit)); // Ensure between 1 and 1000
    
    // Lấy ID của sách không trùng tên (lấy ID nhỏ nhất cho mỗi title)
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books WHERE discount_percent > 0 GROUP BY title");
    $stmt->execute();
    $book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($book_ids)) {
        return 0;
    }
    
    // Lấy đầy đủ thông tin sách từ các ID đã chọn và sắp xếp theo discount_percent
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM books WHERE id IN ($placeholders) ORDER BY discount_percent DESC LIMIT " . $limit);
    $stmt->execute($book_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($books) > 0 ? $books : 0;
}

function get_low_stock_books($conn, $threshold = 5) {
    $threshold = (int)$threshold;
    // Lấy ID của sách không trùng tên (lấy ID nhỏ nhất cho mỗi title)
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books WHERE stock < ? GROUP BY title");
    $stmt->execute([$threshold]);
    $book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($book_ids)) {
        return 0;
    }
    
    // Lấy đầy đủ thông tin sách từ các ID đã chọn
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM books WHERE id IN ($placeholders) ORDER BY stock ASC");
    $stmt->execute($book_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($books) > 0 ? $books : 0;
}

function get_total_books_count($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM books");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function get_total_stock($conn) {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(stock), 0) as total FROM books");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function increment_view_count($conn, $book_id) {
    $stmt = $conn->prepare("UPDATE books SET view_count = view_count + 1 WHERE id = ?");
    return $stmt->execute([$book_id]);
}

function format_price($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

/**
 * Lấy sách ngẫu nhiên với cache 30 phút
 * Sử dụng timestamp để xác định "slot" 30 phút, đảm bảo cùng kết quả trong 30 phút
 */
function get_random_books_cached($conn, $limit = 20) {
    // Tính slot 30 phút hiện tại (1800 giây = 30 phút)
    $cache_slot = floor(time() / 1800);
    $cache_file = sys_get_temp_dir() . '/bookstore_random_books_' . $cache_slot . '.json';
    
    // Kiểm tra cache
    if (file_exists($cache_file)) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
        if ($cached_data && isset($cached_data['book_ids'])) {
            // Lấy sách từ cache IDs
            $book_ids = $cached_data['book_ids'];
            if (!empty($book_ids)) {
                $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
                $stmt = $conn->prepare("SELECT b.*, 
                                               (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.book_id = b.id) AS sold_count
                                        FROM books b WHERE b.id IN ($placeholders)
                                        ORDER BY FIELD(b.id, " . implode(',', $book_ids) . ")");
                $stmt->execute($book_ids);
                $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($books) > 0) {
                    return $books;
                }
            }
        }
    }
    
    // Xóa cache cũ của slot trước
    $old_cache_slot = $cache_slot - 1;
    $old_cache_file = sys_get_temp_dir() . '/bookstore_random_books_' . $old_cache_slot . '.json';
    if (file_exists($old_cache_file)) {
        @unlink($old_cache_file);
    }
    
    // Lấy ID sách không trùng tên
    $stmt = $conn->prepare("SELECT MIN(id) as id FROM books GROUP BY title");
    $stmt->execute();
    $all_book_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($all_book_ids)) {
        return 0;
    }
    
    // Shuffle với seed dựa trên cache_slot để đảm bảo cùng kết quả trong 30 phút
    mt_srand($cache_slot);
    shuffle($all_book_ids);
    mt_srand(); // Reset random seed
    
    // Lấy số lượng cần thiết
    $selected_ids = array_slice($all_book_ids, 0, $limit);
    
    // Lưu cache
    file_put_contents($cache_file, json_encode(['book_ids' => $selected_ids, 'created_at' => time()]));
    
    // Lấy thông tin sách
    $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT b.*, 
                                   (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.book_id = b.id) AS sold_count
                            FROM books b WHERE b.id IN ($placeholders)
                            ORDER BY FIELD(b.id, " . implode(',', $selected_ids) . ")");
    $stmt->execute($selected_ids);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return count($books) > 0 ? $books : 0;
}

/**
 * Lấy thời gian còn lại đến lần refresh tiếp theo (giây)
 */
function get_next_refresh_time() {
    $cache_duration = 1800; // 30 phút
    $current_slot_start = floor(time() / $cache_duration) * $cache_duration;
    $next_refresh = $current_slot_start + $cache_duration;
    return $next_refresh - time();
}
