<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-promotion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/adminlogin.php");
    exit;
}

if (isset($_POST['promotion_id']) && isset($_POST['updates'])) {
    $promotion_id = (int)$_POST['promotion_id'];
    $updates = json_decode($_POST['updates'], true);
    
    if (!is_array($updates) || empty($updates)) {
        header("Location: ../../pages/edit-promotion.php?id=$promotion_id&error=Không có dữ liệu để cập nhật");
        exit;
    }
    
    // Lấy thông tin chương trình để so sánh
    $promotion = get_promotion_by_id($conn, $promotion_id);
    if (!$promotion) {
        header("Location: ../../pages/edit-promotion.php?id=$promotion_id&error=Chương trình không tồn tại");
        exit;
    }
    
    $conn->beginTransaction();
    
    try {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($updates as $update) {
            $book_id = (int)$update['book_id'];
            $custom_discount = isset($update['discount']) ? (int)$update['discount'] : null;
            
            // Validate discount
            if ($custom_discount !== null && ($custom_discount < 1 || $custom_discount > 100)) {
                $error_count++;
                continue;
            }
            
            // Nếu discount bằng với mặc định của chương trình, set NULL
            if ($custom_discount !== null && $custom_discount == $promotion['discount_percent']) {
                $custom_discount = null;
            }
            
            // Kiểm tra xem sách có trong chương trình không
            $check = $conn->prepare("SELECT id FROM promotion_books WHERE promotion_id = ? AND book_id = ?");
            $check->execute([$promotion_id, $book_id]);
            
            if ($check->fetch()) {
                // Update existing
                $stmt = $conn->prepare("UPDATE promotion_books SET custom_discount_percent = ? WHERE promotion_id = ? AND book_id = ?");
                if ($stmt->execute([$custom_discount, $promotion_id, $book_id])) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                // Insert new (nếu sách chưa có trong chương trình)
                $stmt = $conn->prepare("INSERT INTO promotion_books (promotion_id, book_id, custom_discount_percent) VALUES (?, ?, ?)");
                if ($stmt->execute([$promotion_id, $book_id, $custom_discount])) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        $conn->commit();
        
        if ($error_count > 0) {
            header("Location: ../../pages/edit-promotion.php?id=$promotion_id&success=Cập nhật $success_count sách thành công, $error_count sách lỗi");
        } else {
            header("Location: ../../pages/edit-promotion.php?id=$promotion_id&success=Cập nhật thành công $success_count sách");
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        header("Location: ../../pages/edit-promotion.php?id=$promotion_id&error=Lỗi: " . urlencode($e->getMessage()));
    }
    exit;
}

header("Location: ../../pages/admin-coupons.php#programs");
exit;




