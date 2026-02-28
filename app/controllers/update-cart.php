<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . "/../../config/database.php";
include __DIR__ . "/../models/func-cart.php";

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if (!isset($_POST['book_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

try {
    $user_id = $_SESSION['customer_id'];
    $book_id = (int)$_POST['book_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Số lượng phải lớn hơn 0']);
        exit;
    }

    // Kiểm tra stock và lấy giá sau discount (logic cũ - áp dụng promotion cho tất cả sách có trong promotion)
    $stmt = $conn->prepare("SELECT b.stock, b.price,
                                   COALESCE(MAX(pb.custom_discount_percent), MAX(p.discount_percent), b.discount_percent, 0) as final_discount
                            FROM books b
                            LEFT JOIN promotion_books pb ON b.id = pb.book_id
                            LEFT JOIN promotions p ON pb.promotion_id = p.id AND p.is_active = 1 
                                AND p.start_date <= NOW() AND p.end_date >= NOW()
                            WHERE b.id = ?
                            GROUP BY b.id");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        echo json_encode(['success' => false, 'message' => 'Sách không tồn tại']);
        exit;
    }

    if ($quantity > $book['stock']) {
        echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho (chỉ còn ' . $book['stock'] . ' cuốn)']);
        exit;
    }

    // Cập nhật số lượng trong giỏ hàng
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$quantity, $user_id, $book_id]);

    // Tính giá sau discount
    $discount = (float)($book['final_discount'] ?? 0);
    $original_price = (float)$book['price'];
    $final_price = $original_price * (100 - $discount) / 100;
    $subtotal = $final_price * $quantity;

    // Tính lại tổng tiền
    $cart_total = get_cart_total($conn, $user_id);

    echo json_encode([
        'success' => true,
        'subtotal' => $subtotal,
        'total' => $cart_total,
        'message' => 'Đã cập nhật số lượng'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}

