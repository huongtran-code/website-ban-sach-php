<?php

function get_reviews_by_book($conn, $book_id, $limit = 50) {
    $limit = (int)$limit;
    $limit = max(1, min(1000, $limit));
    $stmt = $conn->prepare("SELECT r.*, u.full_name as user_name 
                            FROM reviews r 
                            LEFT JOIN users u ON r.user_id = u.id 
                            WHERE r.book_id = ? 
                            ORDER BY r.created_at DESC 
                            LIMIT " . $limit);
    $stmt->execute([$book_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($reviews) > 0 ? $reviews : [];
}

function get_book_rating_stats($conn, $book_id) {
    $stmt = $conn->prepare("SELECT 
                            AVG(rating) as average_rating,
                            COUNT(*) as review_count,
                            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
                            FROM reviews 
                            WHERE book_id = ?");
    $stmt->execute([$book_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function add_review($conn, $book_id, $user_id, $rating, $comment) {
    try {
        $conn->beginTransaction();
        
        // Kiểm tra xem user đã đánh giá chưa
        $stmt = $conn->prepare("SELECT id FROM reviews WHERE book_id = ? AND user_id = ?");
        $stmt->execute([$book_id, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Bạn đã đánh giá sách này rồi");
        }
        
        // Thêm review
        $stmt = $conn->prepare("INSERT INTO reviews (book_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$book_id, $user_id, $rating, $comment]);
        
        // Cập nhật average rating và review count
        $stats = get_book_rating_stats($conn, $book_id);
        $avg_rating = $stats['average_rating'] ?? 0;
        $review_count = $stats['review_count'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE books SET average_rating = ?, review_count = ? WHERE id = ?");
        $stmt->execute([$avg_rating, $review_count, $book_id]);
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function has_user_reviewed($conn, $book_id, $user_id) {
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE book_id = ? AND user_id = ?");
    $stmt->execute([$book_id, $user_id]);
    return $stmt->fetch() !== false;
}




