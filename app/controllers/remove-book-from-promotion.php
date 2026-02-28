<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-promotion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/adminlogin.php");
    exit;
}

if (isset($_POST['promotion_id']) && isset($_POST['book_id'])) {
    $promotion_id = (int)$_POST['promotion_id'];
    $book_id = (int)$_POST['book_id'];

    try {
        remove_book_from_promotion($conn, $promotion_id, $book_id);
        header("Location: ../../pages/edit-promotion.php?id=$promotion_id&success=Đã xóa sách khỏi chương trình");
    } catch (PDOException $e) {
        header("Location: ../../pages/edit-promotion.php?id=$promotion_id&error=Lỗi: " . urlencode($e->getMessage()));
    }
    exit;
}

header("Location: ../../pages/admin-coupons.php#programs");
exit;
