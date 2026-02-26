<?php
session_start();
include "../db_conn.php";
include "func-promotion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../adminlogin.php");
    exit;
}

if (isset($_POST['promotion_id']) && isset($_POST['book_id']) && !empty($_POST['book_id'])) {
    $promotion_id = (int)$_POST['promotion_id'];
    $book_id = (int)$_POST['book_id'];
    $custom_discount = !empty($_POST['custom_discount']) ? (int)$_POST['custom_discount'] : null;

    if ($book_id <= 0) {
        header("Location: ../edit-promotion.php?id=$promotion_id&error=Vui lòng chọn sách");
        exit;
    }

    if ($custom_discount !== null && ($custom_discount < 1 || $custom_discount > 100)) {
        header("Location: ../edit-promotion.php?id=$promotion_id&error=% giảm giá phải từ 1-100");
        exit;
    }

    try {
        // Kiểm tra xem sách đã có trong promotion khác chưa
        $check_stmt = $conn->prepare("SELECT pb.promotion_id, p.name as promotion_name 
                                      FROM promotion_books pb 
                                      JOIN promotions p ON pb.promotion_id = p.id 
                                      WHERE pb.book_id = ? AND pb.promotion_id != ? 
                                      AND p.is_active = 1 
                                      AND p.start_date <= NOW() AND p.end_date >= NOW()");
        $check_stmt->execute([$book_id, $promotion_id]);
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            header("Location: ../edit-promotion.php?id=$promotion_id&error=Sách này đã có trong chương trình khuyến mãi: " . htmlspecialchars($existing['promotion_name']));
            exit;
        }
        
        $result = add_book_to_promotion($conn, $promotion_id, $book_id, $custom_discount);
        if ($result) {
            header("Location: ../edit-promotion.php?id=$promotion_id&success=Thêm sách vào chương trình thành công");
        } else {
            header("Location: ../edit-promotion.php?id=$promotion_id&error=Không thể thêm sách. Vui lòng thử lại.");
        }
    } catch (Exception $e) {
        header("Location: ../edit-promotion.php?id=$promotion_id&error=Lỗi: " . urlencode($e->getMessage()));
    }
    exit;
} elseif (isset($_POST['promotion_id'])) {
    $promotion_id = (int)$_POST['promotion_id'];
    header("Location: ../edit-promotion.php?id=$promotion_id&error=Vui lòng chọn sách");
    exit;
}

header("Location: ../admin-coupons.php#programs");
exit;
