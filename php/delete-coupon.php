<?php
session_start();
include "../db_conn.php";
include "func-coupon.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    delete_coupon($conn, $id);
    header("Location: ../admin-coupons.php?success=Xóa mã khuyến mãi thành công");
    exit;
} else {
    header("Location: ../admin-coupons.php?error=Yêu cầu không hợp lệ");
    exit;
}





