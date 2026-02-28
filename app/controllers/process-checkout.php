<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-cart.php";
include __DIR__ . "/../models/func-user.php";
include __DIR__ . "/../models/func-transaction.php";
include __DIR__ . "/../models/func-coupon.php";
include __DIR__ . "/../models/func-settings.php";
include __DIR__ . "/../models/func-mail.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../../pages/login.php");
    exit;
}

$user_id = $_SESSION['customer_id'];
$cart_items = get_cart_items($conn, $user_id);
$cart_total = get_cart_total($conn, $user_id);
$user = get_user_by_id($conn, $user_id);

if ($cart_items == 0) {
    header("Location: ../../pages/cart.php?error=Giỏ hàng trống");
    exit;
}

// Lấy thông tin từ form
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$book_types = $_POST['book_type'] ?? [];
$shipping_region = $_POST['shipping_region'] ?? 'hanoi';
// balance | online | cod
$payment_method = $_POST['payment_method'] ?? 'balance';
// balance, cod, online sub-channel (card_demo, momo_demo, zalopay_demo,...)
$payment_channel = $_POST['payment_channel'] ?? $payment_method;

// Lấy địa chỉ từ các trường dropdown hoặc textarea cũ (backward compatible)
$house_number = trim($_POST['house_number'] ?? '');
$city_id = trim($_POST['city'] ?? '');
$district_id = trim($_POST['district'] ?? '');
$ward_id = trim($_POST['ward'] ?? '');
$default_address = trim($_POST['default_address'] ?? ''); // Địa chỉ đầy đủ đã được tạo từ JS

if (empty($full_name) || empty($phone)) {
    header("Location: ../../pages/checkout.php?error=Vui lòng điền đầy đủ thông tin");
    exit;
}

// Nếu có bản cứng thì bắt buộc địa chỉ đầy đủ
$has_hardcopy_for_address = false;
foreach ($book_types as $book_id => $type) {
    if ($type === 'hardcopy') {
        $has_hardcopy_for_address = true;
        break;
    }
}

if ($has_hardcopy_for_address) {
    // Kiểm tra nếu dùng dropdown mới
    if (!empty($city_id) && !empty($district_id) && !empty($ward_id)) {
        // Đã chọn đầy đủ dropdown, kiểm tra số nhà
        if (empty($house_number)) {
            header("Location: ../../pages/checkout.php?error=Vui lòng nhập số nhà / số tầng");
            exit;
        }
        // Sử dụng địa chỉ đầy đủ từ hidden input (đã được JS tạo)
        if (empty($default_address)) {
            header("Location: ../../pages/checkout.php?error=Vui lòng điền đầy đủ địa chỉ giao hàng");
            exit;
        }
    } else {
        // Fallback: kiểm tra textarea cũ (nếu có)
        if (empty($default_address)) {
            header("Location: ../../pages/checkout.php?error=Vui lòng điền đầy đủ địa chỉ giao hàng khi chọn sách bản cứng");
            exit;
        }
    }
}

// Kiểm tra nếu chọn COD thì chỉ được phép khi TẤT CẢ sách đều là bản cứng
$all_hardcopy = true;
foreach ($book_types as $book_id => $type) {
    if ($type !== 'hardcopy') {
        $all_hardcopy = false;
        break;
    }
}

if ($payment_method === 'cod' && !$all_hardcopy) {
    header("Location: ../../pages/checkout.php?error=Thanh toán khi nhận hàng (COD) chỉ áp dụng khi tất cả sách đều là bản cứng");
    exit;
}

try {
    $conn->beginTransaction();

    // Kiểm tra xem đơn hàng có sách bản cứng không
    $has_hardcopy = false;
    foreach ($cart_items as $item) {
        $book_id = $item['book_id'];
        $book_type = $book_types[$book_id] ?? 'pdf';
        if ($book_type === 'hardcopy') {
            $has_hardcopy = true;
            break;
        }
    }

    // Tính phí vận chuyển
    $shipping_fee = 0;
    if ($has_hardcopy) {
        switch ($shipping_region) {
            case 'hanoi':
                $shipping_fee = 20000;
                break;
            case 'north':
                $shipping_fee = 30000;
                break;
            case 'central':
                $shipping_fee = 40000;
                break;
            case 'south':
            default:
                $shipping_fee = 50000;
                break;
        }
    }

    // Tính discount từ hạng thành viên (ưu tiên trước coupon)
    $membership_level = $user['membership_level'] ?? 'normal';
    $membership_discount_percent = get_membership_discount($membership_level);
    $membership_discount_amount = 0;
    $membership_discount_note = '';
    
    if ($membership_discount_percent > 0) {
        $membership_discount_amount = round($cart_total * ($membership_discount_percent / 100), 0);
        $membership_discount_amount = min($membership_discount_amount, $cart_total);
        $membership_name = get_membership_name($membership_level);
        $membership_discount_note = " (giảm $membership_discount_percent% từ hạng $membership_name)";
    }
    
    // Áp dụng mã giảm giá (chỉ trên tiền sách, không áp dụng cho phí ship)
    $coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $coupon_discount_amount = 0;
    $coupon_discount_note = '';

    $is_freeship = false;
    
    if (!empty($coupon_code)) {
        $coupon = get_active_coupon_by_code($conn, $coupon_code);
        if ($coupon) {
            // Kiểm tra mã có áp dụng được cho giỏ hàng không
            $check_result = is_coupon_applicable($coupon, $cart_items);
            if (!$check_result['applicable']) {
                $conn->rollBack();
                header("Location: ../../pages/checkout.php?error=" . urlencode($check_result['message']));
                exit;
            }

            $discount_type = $coupon['discount_type'] ?? 'percent';
            
            if ($discount_type === 'freeship') {
                // Mã freeship - miễn phí vận chuyển
                $is_freeship = true;
                $discount_amount = 0;
                $discount_note = " (áp dụng mã $coupon_code, miễn phí vận chuyển)";
                error_log("Checkout - Freeship coupon applied: $coupon_code");
            } else {
                // Tính toán giảm giá: không giới hạn, có thể giảm 100%
                $discount_percent = (float)$coupon['discount_percent'];
                $coupon_discount_amount = round($cart_total * ($discount_percent / 100), 0);
                
                // Đảm bảo discount không vượt quá tổng tiền sách (không bao gồm phí ship)
                $coupon_discount_amount = min($coupon_discount_amount, $cart_total);
                
                // Debug: Log discount calculation
                error_log("Checkout - Coupon applied: $coupon_code, Discount %: $discount_percent, Discount amount: $coupon_discount_amount");
                
                $coupon_discount_note = " (áp dụng mã $coupon_code, giảm " . number_format($coupon_discount_amount, 0, ',', '.') . "đ)";
            }
            
            // Kiểm tra tổng số lượt sử dụng của coupon (toàn hệ thống)
            if (isset($coupon['usage_limit']) && $coupon['usage_limit'] !== null) {
                $usage_count = $coupon['usage_count'] ?? 0;
                if ($usage_count >= $coupon['usage_limit']) {
                    $conn->rollBack();
                    header("Location: ../../pages/checkout.php?error=" . urlencode('Mã ' . $coupon_code . ' đã hết lượt sử dụng'));
                    exit;
                }
            }
            
            // Kiểm tra số lượt sử dụng của user này (giới hạn per-user)
            if (isset($coupon['max_usage_per_user']) && $coupon['max_usage_per_user'] !== null) {
                $user_usage = get_user_coupon_usage($conn, $coupon['id'], $user_id);
                if ($user_usage >= $coupon['max_usage_per_user']) {
                    $conn->rollBack();
                    header("Location: ../../pages/checkout.php?error=" . urlencode('Bạn đã sử dụng mã ' . $coupon_code . ' đủ ' . $coupon['max_usage_per_user'] . ' lần'));
                    exit;
                }
            }
        }
    }

    // Áp dụng freeship nếu có
    if ($is_freeship) {
        $shipping_fee = 0;
    }
    
    // Tổng discount = membership discount + coupon discount
    // Nhưng không được vượt quá cart_total
    $total_discount_amount = $membership_discount_amount + $coupon_discount_amount;
    if ($total_discount_amount > $cart_total) {
        $total_discount_amount = $cart_total;
    }
    
    // Tính giá trị đơn hàng sau giảm giá (không tính phí ship)
    $order_value_after_discount = max(0, $cart_total - $total_discount_amount);
    
    // Tính phí COD nếu chọn COD (2% giá trị đơn hàng sau giảm giá)
    $cod_fee = 0;
    if ($payment_method === 'cod' && $has_hardcopy) {
        $cod_fee_percent = (float)get_setting($conn, 'cod_fee_percent', 2);
        $cod_fee = round($order_value_after_discount * $cod_fee_percent / 100, 0);
    }
    
    // Tính order_total: (cart_total - total_discount_amount) + shipping_fee + cod_fee
    $order_total = $order_value_after_discount + $shipping_fee + $cod_fee;
    
    // Đảm bảo order_total không âm
    if ($order_total < 0) {
        $order_total = 0;
    }
    
    // Gộp discount notes
    $discount_note = '';
    if ($membership_discount_amount > 0) {
        $discount_note .= $membership_discount_note;
    }
    if ($coupon_discount_amount > 0) {
        $discount_note .= $coupon_discount_note;
    }

    // Kiểm tra số dư (chỉ khi thanh toán bằng balance và order_total > 0)
    if ($payment_method === 'balance' && $order_total > 0 && $user['balance'] < $order_total) {
        $conn->rollBack();
        header("Location: ../../pages/cart.php?error=Số dư không đủ để thanh toán (bao gồm cả phí ship)");
        exit;
    }

    // Nếu chỉ có PDF: status = 'completed', nếu có bản cứng: status = 'pending'
    // Nếu thanh toán COD: status = 'pending_cod' để phân biệt
    if ($payment_method === 'cod') {
        $order_status = 'pending_cod';
    } else {
        $order_status = $has_hardcopy ? 'pending' : 'completed';
    }

    // Tạo đơn hàng - Đảm bảo lưu đúng order_total đã tính
    // Debug: Kiểm tra giá trị trước khi lưu
    // Nếu discount 100% và không có phí ship, order_total phải = 0
    if ($total_discount_amount >= $cart_total && $shipping_fee == 0) {
        $order_total = 0; // Force = 0 để đảm bảo
    }
    
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method, payment_channel) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $order_total, $order_status, $payment_method, $payment_channel]);
    $order_id = $conn->lastInsertId();

    // Thêm order items
    foreach ($cart_items as $item) {
        $book_id = $item['book_id'];
        $book_type = $book_types[$book_id] ?? 'pdf';
        $shipping_address = ($book_type === 'hardcopy' && !empty($default_address)) 
                            ? $default_address 
                            : null;

        $stmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, price, book_type, shipping_address) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $book_id, $item['quantity'], $item['price'], $book_type, $shipping_address]);

        // Nếu là bản cứng, giảm stock
        if ($book_type === 'hardcopy') {
            $stmt = $conn->prepare("UPDATE books SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $book_id]);
        }

        // Nếu là PDF, tạo download history
        if ($book_type === 'pdf') {
            $stmt = $conn->prepare("INSERT INTO download_history (user_id, book_id, order_id, download_count, max_downloads) 
                                    VALUES (?, ?, ?, 0, 3)");
            $stmt->execute([$user_id, $book_id, $order_id]);
        }
    }

    // Trừ tiền từ số dư (chỉ khi thanh toán bằng balance và order_total > 0)
    if ($payment_method === 'balance' && $order_total > 0) {
        $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$order_total, $user_id]);
        
        // Ghi lại giao dịch trừ tiền cho user
        $purchase_desc = "Thanh toán đơn hàng #$order_id";
        add_transaction($conn, $user_id, 'purchase', $order_total, $purchase_desc);
    }

    // Tạo transaction (revenue) - CHỈ khi đơn hàng đã completed (không phải pending_cod)
    // Đơn hàng COD sẽ tạo transaction revenue khi admin xác nhận thanh toán (status = completed)
    if ($order_status == 'completed' && $order_total > 0) {
        $description = "Đơn hàng #$order_id";
        if ($shipping_fee > 0) {
            $description .= " (bao gồm phí ship " . number_format($shipping_fee, 0, ',', '.') . "đ)";
        }
        if ($total_discount_amount > 0) {
            $description .= $discount_note;
        }
        add_transaction($conn, null, 'revenue_order', $order_total, $description);
    } elseif ($order_status == 'completed' && $order_total == 0) {
        // Nếu order_total = 0 (do discount 100%), vẫn tạo transaction để ghi nhận
        $description = "Đơn hàng #$order_id (Miễn phí";
        if ($total_discount_amount > 0) {
            $description .= $discount_note;
        }
        $description .= ")";
        add_transaction($conn, null, 'revenue_order', 0, $description);
    }
    // Nếu pending_cod hoặc pending: KHÔNG tạo transaction revenue ở đây

    // Tăng số lượt sử dụng coupon (nếu có)
    if (!empty($coupon_code) && isset($coupon)) {
        increment_coupon_usage($conn, $coupon['id'], $user_id);
    }

    // Cập nhật total_spent và membership_level chỉ khi đơn hàng completed (PDF) hoặc sẽ được cập nhật khi admin xác nhận (hardcopy)
    // Chỉ cập nhật nếu order_status = 'completed'
    $membership_upgrade = null;
    if ($order_status == 'completed') {
        $order_value = $cart_total - $total_discount_amount; // Giá trị đơn hàng sau discount (không tính phí ship)
        if ($order_value > 0) {
            $membership_upgrade = update_user_membership($conn, $user_id, $order_value);
        }
    }

    // Xóa giỏ hàng
    clear_cart($conn, $user_id);

    $conn->commit();
    
    // Gửi email xác nhận đơn hàng
    try {
        send_order_confirmation_email($conn, $user, (int)$order_id);
    } catch (Exception $mailEx) {
        // Bỏ qua lỗi gửi mail để không ảnh hưởng đến đơn hàng
    }
    
    // Lưu thông báo lên hạng vào session nếu có
    if ($membership_upgrade && $membership_upgrade['upgraded']) {
        $new_level_name = get_membership_name($membership_upgrade['new_level']);
        $_SESSION['membership_upgrade'] = $new_level_name;
    }
    
    header("Location: ../../pages/order-success.php?order_id=$order_id");
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    header("Location: ../../pages/checkout.php?error=Có lỗi xảy ra: " . $e->getMessage());
    exit;
}

