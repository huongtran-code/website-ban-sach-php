<?php
// Trả về danh sách mã giảm giá còn hiệu lực và áp dụng được cho giỏ hàng hiện tại

session_start();
header('Content-Type: application/json; charset=utf-8');

include "../db_conn.php";
include "func-coupon.php";
include "func-cart.php";

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để chọn mã giảm giá.']);
    exit;
}

$user_id = (int) $_SESSION['customer_id'];
$cart_items = get_cart_items($conn, $user_id);

if ($cart_items == 0) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống, không thể chọn mã giảm giá.']);
    exit;
}

try {
    $cart_total = get_cart_total($conn, $user_id);
    $allCoupons = get_all_coupons($conn);

    if ($allCoupons === 0) {
        echo json_encode(['success' => false, 'message' => 'Hiện chưa có mã giảm giá nào.']);
        exit;
    }

    $now = new DateTime();
    $available = [];

    foreach ($allCoupons as $coupon) {
        // Chỉ lấy mã đang active
        if (empty($coupon['is_active']) || (int)$coupon['is_active'] !== 1) {
            continue;
        }

        // Kiểm tra hạn dùng
        if (!empty($coupon['expires_at']) && $coupon['expires_at'] !== '0000-00-00 00:00:00') {
            try {
                $exp = new DateTime($coupon['expires_at']);
                if ($exp <= $now) {
                    continue;
                }
            } catch (Exception $e) {
                // Nếu ngày không hợp lệ, bỏ qua mã này
                continue;
            }
        }

        // Kiểm tra tổng lượt dùng
        if (isset($coupon['usage_limit']) && $coupon['usage_limit'] !== null) {
            $usage_count = (int) ($coupon['usage_count'] ?? 0);
            if ($usage_count >= (int)$coupon['usage_limit']) {
                continue;
            }
        }

        // Kiểm tra lượt dùng mỗi user
        if (isset($coupon['max_usage_per_user']) && $coupon['max_usage_per_user'] !== null) {
            $user_usage = get_user_coupon_usage($conn, (int)$coupon['id'], $user_id);
            if ($user_usage >= (int)$coupon['max_usage_per_user']) {
                continue;
            }
        }

        // Kiểm tra có áp dụng được cho giỏ hiện tại không
        $check = is_coupon_applicable($coupon, $cart_items);
        if (!$check['applicable']) {
            continue;
        }

        // Tính toán mức giảm dự kiến
        $discount_type = $coupon['discount_type'] ?? 'percent';
        $discount_amount = 0;
        $freeship = false;

        if ($discount_type === 'freeship') {
            $freeship = true;
        } else {
            $percent = (float) ($coupon['discount_percent'] ?? 0);
            if ($percent > 0) {
                $discount_amount = round($cart_total * ($percent / 100), 0);
                $discount_amount = min($discount_amount, $cart_total);
            }
        }

        $available[] = [
            'id'               => (int) $coupon['id'],
            'code'             => $coupon['code'],
            'description'      => $coupon['description'] ?? '',
            'discount_type'    => $discount_type,
            'discount_percent' => (int) ($coupon['discount_percent'] ?? 0),
            'discount_amount'  => (int) $discount_amount,
            'freeship'         => $freeship,
            'expires_at'       => $coupon['expires_at'] ?? null,
            'apply_type'       => $coupon['apply_type'] ?? 'all',
        ];
    }

    if (empty($available)) {
        echo json_encode(['success' => false, 'message' => 'Không có mã giảm giá nào phù hợp với giỏ hàng hiện tại.']);
        exit;
    }

    echo json_encode([
        'success'  => true,
        'coupons'  => $available,
        'message'  => 'Danh sách mã giảm giá khả dụng đã được tải.',
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi lấy danh sách mã giảm giá.']);
}

