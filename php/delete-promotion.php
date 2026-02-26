<?php
session_start();
include "../db_conn.php";
include "func-promotion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../adminlogin.php");
    exit;
}

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        delete_promotion($conn, $id);
        header("Location: ../admin-coupons.php?success=Xóa chương trình thành công#programs");
    } catch (PDOException $e) {
        header("Location: ../admin-coupons.php?error=Lỗi: " . urlencode($e->getMessage()) . "#programs");
    }
    exit;
}

header("Location: ../admin-coupons.php#programs");
exit;
