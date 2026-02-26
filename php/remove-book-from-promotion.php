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

    try {
        remove_book_from_promotion($conn, $promotion_id, $book_id);
        header("Location: ../edit-promotion.php?id=$promotion_id&success=Đã xóa sách khỏi chương trình");
    } catch (PDOException $e) {
        header("Location: ../edit-promotion.php?id=$promotion_id&error=Lỗi: " . urlencode($e->getMessage()));
    }
    exit;
}

header("Location: ../admin-coupons.php#programs");
exit;
