<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_POST['book_title']) && isset($_POST['book_description']) && 
    isset($_POST['book_author']) && isset($_POST['book_category'])) {
    
    $title = $_POST['book_title'];
    $desc = $_POST['book_description'];
    $author_id = $_POST['book_author'];
    $category_id = $_POST['book_category'];
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $discount_percent = isset($_POST['discount_percent']) && $_POST['discount_percent'] !== '' 
                        ? (int)$_POST['discount_percent'] 
                        : 0;
    $return_days = isset($_POST['return_days']) && $_POST['return_days'] !== ''
                        ? (int)$_POST['return_days']
                        : 7;

    if (empty($title) || empty($desc) || $author_id == 0 || $category_id == 0) {
        header("Location: ../../pages/admin-books.php?tab=add&error=Vui lòng điền đầy đủ tất cả các trường&title=$title&desc=$desc&author_id=$author_id&category_id=$category_id");
        exit;
    }

    if ($price < 0) {
        header("Location: ../../pages/admin-books.php?tab=add&error=Giá tiền không hợp lệ&title=$title&desc=$desc&author_id=$author_id&category_id=$category_id&price=$price&stock=$stock&discount=$discount_percent");
        exit;
    }

    if ($stock < 0) {
        header("Location: ../../pages/admin-books.php?tab=add&error=Số lượng không hợp lệ&title=$title&desc=$desc&author_id=$author_id&category_id=$category_id&price=$price&stock=$stock&discount=$discount_percent");
        exit;
    }

    if ($discount_percent < 0 || $discount_percent > 100) {
        header("Location: ../../pages/admin-books.php?tab=add&error=% Giảm giá phải từ 0-100&title=$title&desc=$desc&author_id=$author_id&category_id=$category_id&price=$price&stock=$stock&discount=$discount_percent");
        exit;
    }

    if ($return_days < 0 || $return_days > 365) {
        header("Location: ../../pages/admin-books.php?tab=add&error=Số ngày hoàn trả phải từ 0-365&title=$title&desc=$desc&author_id=$author_id&category_id=$category_id&price=$price&stock=$stock&discount=$discount_percent&return_days=$return_days");
        exit;
    }

    $cover = $_FILES['book_cover'];
    $file = $_FILES['file'];

    if ($cover['error'] != 0 || $file['error'] != 0) {
        header("Location: ../../pages/admin-books.php?tab=add&error=Vui lòng tải lên bìa sách và file&title=$title&desc=$desc&author_id=$author_id&category_id=$category_id");
        exit;
    }

    $cover_name = time() . "_" . $cover['name'];
    $file_name = time() . "_" . $file['name'];

    move_uploaded_file($cover['tmp_name'], __DIR__ . "/../../storage/uploads/cover/" . $cover_name);
    move_uploaded_file($file['tmp_name'], __DIR__ . "/../../storage/uploads/files/" . $file_name);

    // Xác định các flag
    $is_promotion = $discount_percent > 0 ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO books (title, author_id, description, category_id, cover, file, price, stock, discount_percent, is_promotion, is_new, return_days) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
    $stmt->execute([$title, $author_id, $desc, $category_id, $cover_name, $file_name, $price, $stock, $discount_percent, $is_promotion, $return_days]);

    header("Location: ../../pages/admin-books.php?tab=list&success=Thêm sách thành công");
    exit;
} else {
    header("Location: ../../pages/admin-books.php?tab=add&error=Vui lòng điền đầy đủ tất cả các trường");
    exit;
}
