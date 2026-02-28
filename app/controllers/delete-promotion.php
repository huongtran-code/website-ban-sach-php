<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-promotion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/adminlogin.php");
    exit;
}

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        delete_promotion($conn, $id);
        header("Location: ../../pages/admin-coupons.php?success=Xóa chương trình thành công#programs");
    } catch (PDOException $e) {
        header("Location: ../../pages/admin-coupons.php?error=Lỗi: " . urlencode($e->getMessage()) . "#programs");
    }
    exit;
}

header("Location: ../../pages/admin-coupons.php#programs");
exit;
