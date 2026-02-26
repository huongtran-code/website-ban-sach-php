<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['author_id']) && isset($_POST['author_name'])) {
    $id = $_POST['author_id'];
    $name = $_POST['author_name'];

    if (empty($name)) {
        header("Location: ../edit-author.php?id=$id&error=Vui lòng nhập tên tác giả");
        exit;
    }

    $stmt = $conn->prepare("UPDATE authors SET name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);

    header("Location: ../admin.php?success=Cập nhật tác giả thành công");
    exit;
} else {
    header("Location: ../admin.php?error=Yêu cầu không hợp lệ");
    exit;
}
