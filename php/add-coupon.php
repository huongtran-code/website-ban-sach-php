<?php
session_start();
include "../db_conn.php";
include "func-coupon.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['code'])) {
    $code = strtoupper(trim($_POST['code']));
    $description = trim($_POST['description'] ?? '');
    $discount_type = $_POST['discount_type'] ?? 'percent';
    $discount_percent = ($discount_type === 'freeship') ? 0 : (int)($_POST['discount_percent'] ?? 0);
    $apply_type = $_POST['apply_type'] ?? 'all';
    $apply_to_ids = null;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $max_usage_per_user = !empty($_POST['max_usage_per_user']) ? (int)$_POST['max_usage_per_user'] : null;

    if (empty($code)) {
        header("Location: ../admin-coupons.php?error=Vui lòng nhập mã khuyến mãi");
        exit;
    }

    if ($discount_type === 'percent' && ($discount_percent < 1 || $discount_percent > 100)) {
        header("Location: ../admin-coupons.php?error=% giảm giá phải từ 1-100");
        exit;
    }

    // Xử lý apply_to_ids
    if ($apply_type === 'category' || $apply_type === 'book') {
        if (isset($_POST['apply_to_ids']) && is_array($_POST['apply_to_ids']) && count($_POST['apply_to_ids']) > 0) {
            $apply_to_ids = array_map('intval', $_POST['apply_to_ids']);
        } else {
            header("Location: ../admin-coupons.php?error=Vui lòng chọn " . ($apply_type === 'category' ? 'danh mục' : 'sách') . " để áp dụng");
            exit;
        }
    }

    if ($usage_limit !== null && $usage_limit < 1) {
        header("Location: ../admin-coupons.php?error=Số lượt sử dụng phải lớn hơn 0");
        exit;
    }

    if ($max_usage_per_user !== null && $max_usage_per_user < 1) {
        header("Location: ../admin-coupons.php?error=Số lượt mỗi user phải lớn hơn 0");
        exit;
    }

    try {
        add_coupon($conn, $code, $description, $discount_percent, $apply_type, $apply_to_ids, $expires_at, $usage_limit, $max_usage_per_user, $discount_type);
        header("Location: ../admin-coupons.php?success=Thêm mã khuyến mãi thành công");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            header("Location: ../admin-coupons.php?error=Mã khuyến mãi đã tồn tại");
        } else {
            header("Location: ../admin-coupons.php?error=Lỗi: " . urlencode($e->getMessage()));
        }
    }
    exit;
} else {
    header("Location: ../admin-coupons.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}


