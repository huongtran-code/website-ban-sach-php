<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../admin.php?success=Xóa thể loại thành công");
    exit;
} else {
    header("Location: ../admin.php?error=ID thể loại không hợp lệ");
    exit;
}
