<?php
session_start();
include "../db_conn.php";
include "func-promotion.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../adminlogin.php");
    exit;
}

if (isset($_POST['id']) && isset($_POST['is_active'])) {
    $id = (int)$_POST['id'];
    $is_active = (int)$_POST['is_active'];

    try {
        toggle_promotion_status($conn, $id, $is_active);
        $action = $is_active ? 'Kích hoạt' : 'Tạm tắt';
        header("Location: ../admin-coupons.php?success=$action chương trình thành công#programs");
    } catch (PDOException $e) {
        header("Location: ../admin-coupons.php?error=Lỗi: " . urlencode($e->getMessage()) . "#programs");
    }
    exit;
}

header("Location: ../admin-coupons.php#programs");
exit;
