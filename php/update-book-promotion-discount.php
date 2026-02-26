<?php
session_start();
include "../db_conn.php";
include "func-promotion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../adminlogin.php");
    exit;
}

if (isset($_POST['promotion_id']) && isset($_POST['book_id'])) {
    $promotion_id = (int)$_POST['promotion_id'];
    $book_id = (int)$_POST['book_id'];
    $custom_discount = !empty($_POST['custom_discount']) ? (int)$_POST['custom_discount'] : null;

    // Lấy thông tin chương trình để so sánh
    $promotion = get_promotion_by_id($conn, $promotion_id);
    
    // Nếu discount bằng với mặc định của chương trình, set NULL
    if ($custom_discount !== null && $promotion && $custom_discount == $promotion['discount_percent']) {
        $custom_discount = null;
    }

    if ($custom_discount !== null && ($custom_discount < 1 || $custom_discount > 100)) {
        header("Location: ../edit-promotion.php?id=$promotion_id&error=% giảm giá phải từ 1-100");
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE promotion_books SET custom_discount_percent = ? WHERE promotion_id = ? AND book_id = ?");
        if ($stmt->execute([$custom_discount, $promotion_id, $book_id])) {
            header("Location: ../edit-promotion.php?id=$promotion_id&success=Cập nhật % giảm giá thành công");
        } else {
            header("Location: ../edit-promotion.php?id=$promotion_id&error=Không thể cập nhật");
        }
    } catch (PDOException $e) {
        header("Location: ../edit-promotion.php?id=$promotion_id&error=Lỗi: " . urlencode($e->getMessage()));
    }
    exit;
}

header("Location: ../admin-coupons.php#programs");
exit;
