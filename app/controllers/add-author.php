<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_POST['author_name'])) {
    $name = $_POST['author_name'];

    if (empty($name)) {
        header("Location: ../../pages/admin-authors.php?tab=add&error=Vui lòng nhập tên tác giả&name=" . urlencode($name));
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO authors (name) VALUES (?)");
    $stmt->execute([$name]);

    header("Location: ../../pages/admin-authors.php?tab=list&success=Thêm tác giả thành công");
    exit;
} else {
    header("Location: ../../pages/admin-authors.php?tab=add&error=Vui lòng điền đầy đủ tất cả các trường");
    exit;
}
