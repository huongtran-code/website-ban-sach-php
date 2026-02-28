<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_POST['book_id']) && isset($_POST['book_title']) && isset($_POST['book_description']) && 
    isset($_POST['book_author']) && isset($_POST['book_category'])) {
    
    $id = $_POST['book_id'];
    $title = $_POST['book_title'];
    $desc = $_POST['book_description'];
    $author_id = $_POST['book_author'];
    $category_id = $_POST['book_category'];
    $price = isset($_POST['book_price']) ? (float)$_POST['book_price'] : 0;
    $stock = isset($_POST['book_stock']) ? (int)$_POST['book_stock'] : 0;
    $discount_percent = isset($_POST['discount_percent']) && $_POST['discount_percent'] !== '' 
                        ? (int)$_POST['discount_percent'] 
                        : 0;
    $return_days = isset($_POST['return_days']) && $_POST['return_days'] !== ''
                        ? (int)$_POST['return_days']
                        : 7;

    if (empty($title) || empty($desc) || $author_id == 0 || $category_id == 0) {
        header("Location: ../../pages/edit-book.php?id=$id&error=Vui lòng điền đầy đủ tất cả các trường");
        exit;
    }

    if ($discount_percent < 0 || $discount_percent > 100) {
        header("Location: ../../pages/edit-book.php?id=$id&error=% Giảm giá phải từ 0-100");
        exit;
    }

    if ($return_days < 0 || $return_days > 365) {
        header("Location: ../../pages/edit-book.php?id=$id&error=Số ngày hoàn trả phải từ 0-365");
        exit;
    }

    $cover = $_FILES['book_cover'];
    $file = $_FILES['file'];

    if ($cover['error'] == 0) {
        $stmt = $conn->prepare("SELECT cover FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);
        @unlink(__DIR__ . "/../../storage/uploads/cover/" . $old['cover']);

        $cover_name = time() . "_" . $cover['name'];
        move_uploaded_file($cover['tmp_name'], __DIR__ . "/../../storage/uploads/cover/" . $cover_name);

        $stmt = $conn->prepare("UPDATE books SET cover = ? WHERE id = ?");
        $stmt->execute([$cover_name, $id]);
    }

    if ($file['error'] == 0) {
        $stmt = $conn->prepare("SELECT file FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);
        @unlink(__DIR__ . "/../../storage/uploads/files/" . $old['file']);

        $file_name = time() . "_" . $file['name'];
        move_uploaded_file($file['tmp_name'], __DIR__ . "/../../storage/uploads/files/" . $file_name);

        $stmt = $conn->prepare("UPDATE books SET file = ? WHERE id = ?");
        $stmt->execute([$file_name, $id]);
    }

    // Xác định is_promotion flag
    $is_promotion = $discount_percent > 0 ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE books SET title = ?, author_id = ?, description = ?, category_id = ?, price = ?, stock = ?, discount_percent = ?, is_promotion = ?, return_days = ? WHERE id = ?");
    $stmt->execute([$title, $author_id, $desc, $category_id, $price, $stock, $discount_percent, $is_promotion, $return_days, $id]);

    header("Location: ../../pages/admin.php?success=Cập nhật sách thành công");
    exit;
} else {
    header("Location: ../../pages/admin.php?error=Yêu cầu không hợp lệ");
    exit;
}
