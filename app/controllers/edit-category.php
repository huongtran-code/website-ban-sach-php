<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_POST['category_id']) && isset($_POST['category_name'])) {
    $id = $_POST['category_id'];
    $name = $_POST['category_name'];

    if (empty($name)) {
        header("Location: ../../pages/edit-category.php?id=$id&error=Vui lòng nhập tên thể loại");
        exit;
    }

    $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    header("Location: ../../pages/admin.php?success=Cập nhật thể loại thành công");
    exit;
} else {
    header("Location: ../../pages/admin.php?error=Yêu cầu không hợp lệ");
    exit;
}
