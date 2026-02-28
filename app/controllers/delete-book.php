<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("SELECT cover, file FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        @unlink(__DIR__ . "/../../storage/uploads/cover/" . $book['cover']);
        @unlink(__DIR__ . "/../../storage/uploads/files/" . $book['file']);
    }

    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../../pages/admin.php?success=Xóa sách thành công");
    exit;
} else {
    header("Location: ../../pages/admin.php?error=ID sách không hợp lệ");
    exit;
}
