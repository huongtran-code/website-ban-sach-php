<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_POST['category_name'])) {
    $name = $_POST['category_name'];

    if (empty($name)) {
        header("Location: ../../pages/admin-categories.php?tab=add&error=Vui lòng nhập tên thể loại&name=" . urlencode($name));
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);

    header("Location: ../../pages/admin-categories.php?tab=list&success=Thêm thể loại thành công");
    exit;
} else {
    header("Location: ../../pages/admin-categories.php?tab=add&error=Vui lòng điền đầy đủ tất cả các trường");
    exit;
}
