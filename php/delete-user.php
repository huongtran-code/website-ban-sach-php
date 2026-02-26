<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../admin-users.php?success=Xóa người dùng thành công");
    exit;
} else {
    header("Location: ../admin-users.php?error=ID không hợp lệ");
    exit;
}
