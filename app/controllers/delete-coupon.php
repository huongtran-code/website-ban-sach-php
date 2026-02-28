<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-coupon.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    delete_coupon($conn, $id);
    header("Location: ../../pages/admin-coupons.php?success=Xóa mã khuyến mãi thành công");
    exit;
} else {
    header("Location: ../../pages/admin-coupons.php?error=Yêu cầu không hợp lệ");
    exit;
}





