<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM authors WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../../pages/admin.php?success=Xóa tác giả thành công");
    exit;
} else {
    header("Location: ../../pages/admin.php?error=ID tác giả không hợp lệ");
    exit;
}
