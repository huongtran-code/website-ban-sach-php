<?php
session_start();
require_once __DIR__ . "/../config/bootstrap.php";
include MODELS_PATH . "func-book.php";

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php?error=Vui lòng đăng nhập");
    exit;
}

$user_id = $_SESSION['customer_id'];
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$book_id || !$order_id) {
    header("Location: index.php?error=Thông tin không hợp lệ");
    exit;
}

// Kiểm tra xem user có quyền download không
$stmt = $conn->prepare("SELECT dh.*, oi.book_type 
                        FROM download_history dh
                        JOIN order_items oi ON dh.order_id = oi.order_id AND dh.book_id = oi.book_id
                        WHERE dh.user_id = ? AND dh.book_id = ? AND dh.order_id = ?");
$stmt->execute([$user_id, $book_id, $order_id]);
$download_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$download_info) {
    header("Location: index.php?error=Bạn chưa mua sách này");
    exit;
}

// Kiểm tra số lần download
if ($download_info['download_count'] >= $download_info['max_downloads']) {
    header("Location: index.php?error=Bạn đã hết lượt download (tối đa {$download_info['max_downloads']} lượt)");
    exit;
}

// Lấy thông tin sách
$book = get_book_by_id($conn, $book_id);
if (!$book) {
    header("Location: index.php?error=Không tìm thấy sách");
    exit;
}

// Cập nhật số lần download
$stmt = $conn->prepare("UPDATE download_history 
                        SET download_count = download_count + 1, 
                            last_download_at = NOW() 
                        WHERE user_id = ? AND book_id = ? AND order_id = ?");
$stmt->execute([$user_id, $book_id, $order_id]);

// Đếm số lần còn lại
$remaining = $download_info['max_downloads'] - $download_info['download_count'] - 1;

// Download file
$file_path = __DIR__ . "/../storage/uploads/files/" . $book['file'];
if (file_exists($file_path)) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $book['title'] . '.pdf"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    
    // Hiển thị thông báo sau khi download
    echo "<script>
        alert('Download thành công! Bạn còn " . $remaining . " lượt download.');
        window.location.href = 'my-orders.php';
    </script>";
    exit;
} else {
    header("Location: index.php?error=File không tồn tại");
    exit;
}





