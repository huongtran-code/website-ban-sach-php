<?php
session_start();
include "../db_conn.php";
include "func-coupon.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['id']) && isset($_POST['is_active'])) {
    $id = (int)$_POST['id'];
    $is_active = (int)$_POST['is_active'];

    update_coupon_status($conn, $id, $is_active);
    header("Location: ../admin-coupons.php?success=Cập nhật trạng thái mã thành công");
    exit;
} else {
    header("Location: ../admin-coupons.php?error=Yêu cầu không hợp lệ");
    exit;
}





