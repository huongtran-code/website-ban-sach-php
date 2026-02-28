<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-coupon.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../pages/adminlogin.php");
    exit;
}

if (isset($_POST['id']) && isset($_POST['code']) && isset($_POST['discount_percent'])) {
    $id = (int)$_POST['id'];
    $code = strtoupper(trim($_POST['code']));
    $description = trim($_POST['description'] ?? '');
    $discount_percent = (int)$_POST['discount_percent'];
    $apply_type = $_POST['apply_type'] ?? 'all';
    $apply_to_ids = null;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $max_usage_per_user = !empty($_POST['max_usage_per_user']) ? (int)$_POST['max_usage_per_user'] : null;

    if (empty($code)) {
        header("Location: ../../pages/edit-coupon.php?id=$id&error=Vui lòng nhập mã khuyến mãi");
        exit;
    }

    if ($discount_percent < 1 || $discount_percent > 100) {
        header("Location: ../../pages/edit-coupon.php?id=$id&error=% giảm giá phải từ 1-100");
        exit;
    }

    // Xử lý apply_to_ids
    if ($apply_type === 'category' || $apply_type === 'book') {
        if (isset($_POST['apply_to_ids']) && is_array($_POST['apply_to_ids']) && count($_POST['apply_to_ids']) > 0) {
            $apply_to_ids = array_map('intval', $_POST['apply_to_ids']);
        } else {
            header("Location: ../../pages/edit-coupon.php?id=$id&error=Vui lòng chọn " . ($apply_type === 'category' ? 'danh mục' : 'sách') . " để áp dụng");
            exit;
        }
    }

    if ($usage_limit !== null && $usage_limit < 1) {
        header("Location: ../../pages/edit-coupon.php?id=$id&error=Số lượt sử dụng phải lớn hơn 0");
        exit;
    }

    if ($max_usage_per_user !== null && $max_usage_per_user < 1) {
        header("Location: ../../pages/edit-coupon.php?id=$id&error=Số lượt mỗi user phải lớn hơn 0");
        exit;
    }

    // Kiểm tra mã có trùng với mã khác không (trừ chính nó)
    $existing_coupon = get_coupon_by_id($conn, $id);
    if (!$existing_coupon) {
        header("Location: ../../pages/admin-coupons.php?error=Mã khuyến mãi không tồn tại");
        exit;
    }

    if ($code !== $existing_coupon['code']) {
        // Kiểm tra mã mới có trùng không
        $check_stmt = $conn->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
        $check_stmt->execute([$code, $id]);
        if ($check_stmt->fetch()) {
            header("Location: ../../pages/edit-coupon.php?id=$id&error=Mã khuyến mãi đã tồn tại");
            exit;
        }
    }

    try {
        if (update_coupon($conn, $id, $code, $description, $discount_percent, $apply_type, $apply_to_ids, $expires_at, $usage_limit, $max_usage_per_user)) {
            header("Location: ../../pages/admin-coupons.php?success=Sửa mã khuyến mãi thành công");
        } else {
            header("Location: ../../pages/edit-coupon.php?id=$id&error=Không thể cập nhật mã khuyến mãi");
        }
    } catch (PDOException $e) {
        header("Location: ../../pages/edit-coupon.php?id=$id&error=Lỗi: " . urlencode($e->getMessage()));
    }
    exit;
} else {
    header("Location: ../../pages/admin-coupons.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}




