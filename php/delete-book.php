<?php
session_start();
include "../db_conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("SELECT cover, file FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        @unlink("../uploads/cover/" . $book['cover']);
        @unlink("../uploads/files/" . $book['file']);
    }

    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: ../admin.php?success=Xóa sách thành công");
    exit;
} else {
    header("Location: ../admin.php?error=ID sách không hợp lệ");
    exit;
}
