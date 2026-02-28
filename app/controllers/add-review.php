<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-review.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../../pages/login.php?error=Vui lòng đăng nhập để đánh giá");
    exit;
}

if (isset($_POST['book_id']) && isset($_POST['rating'])) {
    $book_id = (int)$_POST['book_id'];
    $user_id = $_SESSION['customer_id'];
    $rating = (int)$_POST['rating'];
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    if ($rating < 1 || $rating > 5) {
        header("Location: ../../pages/book-detail.php?id=$book_id&error=Đánh giá không hợp lệ");
        exit;
    }
    
    try {
        add_review($conn, $book_id, $user_id, $rating, $comment);
        header("Location: ../../pages/book-detail.php?id=$book_id&success=Đánh giá của bạn đã được gửi thành công!");
    } catch (Exception $e) {
        header("Location: ../../pages/book-detail.php?id=$book_id&error=" . urlencode($e->getMessage()));
    }
    exit;
} else {
    header("Location: ../../pages/index.php?error=Yêu cầu không hợp lệ");
    exit;
}




