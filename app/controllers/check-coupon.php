<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-coupon.php";
include __DIR__ . "/../models/func-cart.php";

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['valid' => false, 'message' => 'Vui lòng đăng nhập']);
        exit;
    }

    $user_id = $_SESSION['customer_id'];
    $code = strtoupper(trim($_GET['code'] ?? ''));

    if (empty($code)) {
        echo json_encode(['valid' => false, 'message' => 'Vui lòng nhập mã giảm giá']);
        exit;
    }

    $coupon = get_active_coupon_by_code($conn, $code);

    if (!$coupon) {
        echo json_encode(['valid' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn']);
        exit;
    }

    // Kiểm tra tổng số lượt sử dụng của coupon (toàn hệ thống)
    if (isset($coupon['usage_limit']) && $coupon['usage_limit'] !== null) {
        $usage_count = $coupon['usage_count'] ?? 0;
        if ($usage_count >= $coupon['usage_limit']) {
            echo json_encode(['valid' => false, 'message' => 'Mã ' . $coupon['code'] . ' đã hết lượt sử dụng']);
            exit;
        }
    }

    // Kiểm tra số lượt sử dụng của user này (giới hạn per-user)
    if (isset($coupon['max_usage_per_user']) && $coupon['max_usage_per_user'] !== null) {
        $user_usage = get_user_coupon_usage($conn, $coupon['id'], $user_id);
        if ($user_usage >= $coupon['max_usage_per_user']) {
            echo json_encode(['valid' => false, 'message' => 'Bạn đã sử dụng mã ' . $coupon['code'] . ' đủ ' . $coupon['max_usage_per_user'] . ' lần']);
            exit;
        }
    }

    // Kiểm tra mã có áp dụng được cho giỏ hàng không
    $cart_items = get_cart_items($conn, $user_id);

if ($cart_items == 0) {
    echo json_encode(['valid' => false, 'message' => 'Giỏ hàng trống']);
    exit;
}

$check_result = is_coupon_applicable($coupon, $cart_items);
if (!$check_result['applicable']) {
    echo json_encode(['valid' => false, 'message' => $check_result['message']]);
    exit;
}

    // Kiểm tra loại mã giảm giá
    $discount_type = $coupon['discount_type'] ?? 'percent';
    $cart_total = get_cart_total($conn, $user_id);
    
    if ($discount_type === 'freeship') {
        // Mã freeship - không giảm giá sách, chỉ miễn phí vận chuyển
        echo json_encode([
            'valid' => true,
            'code' => $coupon['code'],
            'discount_type' => 'freeship',
            'discount_percent' => 0,
            'discount_amount' => 0,
            'freeship' => true,
            'message' => 'Đã áp dụng mã ' . $coupon['code'] . ': Miễn phí vận chuyển!',
            'description' => $coupon['description'] ?? ''
        ]);
    } else {
        // Mã giảm giá %
        $discount_amount = round($cart_total * ($coupon['discount_percent'] / 100));
        
        // Đảm bảo discount không vượt quá tổng tiền sách
        $discount_amount = min($discount_amount, $cart_total);

        echo json_encode([
            'valid' => true,
            'code' => $coupon['code'],
            'discount_type' => 'percent',
            'discount_percent' => $coupon['discount_percent'],
            'discount_amount' => $discount_amount,
            'freeship' => false,
            'message' => 'Đã áp dụng mã ' . $coupon['code'] . ': giảm ' . $coupon['discount_percent'] . '% giá trị sách.',
            'description' => $coupon['description'] ?? ''
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'message' => 'Có lỗi xảy ra khi kiểm tra mã. Vui lòng thử lại.'
    ]);
}

